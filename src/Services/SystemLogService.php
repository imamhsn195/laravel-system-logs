<?php

namespace ImamHasan\SystemLogs\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SystemLogService
{
    protected string $logDirectory;
    
    public function __construct(string $logDirectory)
    {
        $this->logDirectory = $logDirectory;
    }
    
    /**
     * List available log files.
     */
    public function listFiles(?string $channel = null, ?string $date = null, ?bool $recursive = null): Collection
    {
        if (!File::isDirectory($this->logDirectory)) {
            return collect();
        }
        
        $recursive = $recursive ?? config('system-logs.scanning.recursive', true);
        $maxDepth = config('system-logs.scanning.max_depth', 10);
        $excludeDirs = config('system-logs.scanning.exclude_directories', []);
        
        if ($recursive) {
            $files = $this->scanRecursively($maxDepth, $excludeDirs);
        } else {
            $files = collect(File::files($this->logDirectory));
        }
        
        return $files
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.log'))
            ->map(fn ($file) => $this->mapFileInfo($file, $recursive))
            ->when($channel, fn (Collection $collection) => $collection->where('channel', $channel))
            ->when($date, fn (Collection $collection) => $collection->filter(
                fn (array $file) => Str::contains($file['name'], $date)
            ))
            ->sortByDesc('updated_at')
            ->values();
    }
    
    /**
     * Scan directory recursively.
     */
    protected function scanRecursively(int $maxDepth, array $excludeDirs): Collection
    {
        $files = collect();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->logDirectory,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $depth = $iterator->getDepth();
            
            // Check max depth
            if ($maxDepth > 0 && $depth >= $maxDepth) {
                continue;
            }
            
            // Check excluded directories
            if ($file->isDir()) {
                $dirName = $file->getFilename();
                if (in_array($dirName, $excludeDirs)) {
                    $iterator->next();
                    continue;
                }
            }
            
            // Only add .log files
            if ($file->isFile() && Str::endsWith($file->getFilename(), '.log')) {
                $files->push($file);
            }
        }
        
        return $files;
    }
    
    /**
     * Map file info to array.
     */
    protected function mapFileInfo($file, bool $recursive): array
    {
        $name = $file->getFilename();
        $channel = $this->inferChannel($name);
        $updatedAt = Carbon::createFromTimestamp($file->getMTime());
        
        // Get relative path for display
        $relativePath = $recursive 
            ? str_replace($this->logDirectory . DIRECTORY_SEPARATOR, '', $file->getPathname())
            : $name;
        
        return [
            'name' => $name,
            'relative_path' => $relativePath,
            'full_path' => $file->getRealPath(),
            'channel' => $channel,
            'size' => (int) $file->getSize(),
            'size_human' => $this->formatBytes((int) $file->getSize()),
            'updated_at' => $updatedAt,
            'updated_for_humans' => $updatedAt->diffForHumans(),
        ];
    }
    
    /**
     * Infer channel from filename.
     */
    protected function inferChannel(string $filename): string
    {
        if (Str::contains($filename, 'laravel')) {
            return 'single';
        }
        
        if (Str::contains($filename, 'daily')) {
            return 'daily';
        }
        
        if (Str::contains($filename, 'stack')) {
            return 'stack';
        }
        
        // Extract channel from filename pattern: channel-name.log
        $parts = explode('-', pathinfo($filename, PATHINFO_FILENAME));
        return $parts[0] ?? 'single';
    }
    
    /**
     * Get log entries with filters.
     */
    public function getEntries(array $filters = [], int $limit = 50): array
    {
        $maxFiles = $filters['max_files'] ?? config('system-logs.filters.default_max_files', 3);
        $files = $this->listFiles(
            $filters['channel'] ?? null,
            $filters['date'] ?? null
        )->take($maxFiles);
        
        $allEntries = collect();
        $filesScanned = 0;
        
        foreach ($files as $file) {
            $entries = $this->parseLogFile($file['relative_path']);
            $allEntries = $allEntries->merge($entries);
            $filesScanned++;
        }
        
        // Apply filters
        $filteredEntries = $this->applyFilters($allEntries, $filters);
        
        // Sort by timestamp descending
        $sortedEntries = $filteredEntries->sortByDesc(fn ($entry) => $entry['timestamp']->timestamp);
        
        // Paginate
        $paginated = $sortedEntries->take($limit);
        
        return [
            'entries' => $paginated->values(),
            'files' => $files,
            'meta' => [
                'files_scanned' => $filesScanned,
                'total_entries' => $filteredEntries->count(),
                'limit' => $limit,
                'filters_applied' => $filters,
            ],
        ];
    }
    
    /**
     * Parse log file.
     */
    protected function parseLogFile(string $filePath): Collection
    {
        $fullPath = $this->logDirectory . DIRECTORY_SEPARATOR . $filePath;
        
        // Security check
        if (!$this->isValidLogFile($fullPath)) {
            return collect();
        }
        
        if (!File::exists($fullPath)) {
            return collect();
        }
        
        $maxLines = config('system-logs.filters.max_lines_per_file', 5000);
        $readFromEnd = config('system-logs.filters.read_from_end', true);
        
        // Read lines efficiently
        $lines = $this->readLogFileLines($fullPath, $maxLines, $readFromEnd);
        
        $entries = collect();
        $currentEntry = null;
        $pattern = config('system-logs.parsing.entry_pattern');
        
        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $matches)) {
                // Save previous entry if exists
                if ($currentEntry) {
                    $entries->push($currentEntry);
                }
                
                // Start new entry
                $currentEntry = $this->createLogEntry($matches, $filePath);
            } elseif ($currentEntry && !empty(trim($line))) {
                // Continuation of previous entry
                $currentEntry['message'] .= "\n" . $line;
            }
        }
        
        // Add last entry
        if ($currentEntry) {
            $entries->push($currentEntry);
        }
        
        return $entries;
    }
    
    /**
     * Read log file lines efficiently, optionally from the end.
     */
    protected function readLogFileLines(string $filePath, int $maxLines, bool $readFromEnd): array
    {
        if (!File::exists($filePath)) {
            return [];
        }
        
        $fileSize = File::size($filePath);
        
        // For small files, read normally
        if ($fileSize < 5 * 1024 * 1024) { // Less than 5MB
            $content = File::get($filePath);
            $allLines = explode("\n", $content);
            
            if ($readFromEnd && count($allLines) > $maxLines) {
                // Return last N lines
                return array_slice($allLines, -$maxLines);
            }
            
            return $allLines;
        }
        
        // For large files, read from end using file pointer
        if ($readFromEnd) {
            return $this->readLinesFromEnd($filePath, $maxLines);
        }
        
        // Read from beginning with limit
        return $this->readLinesFromStart($filePath, $maxLines);
    }
    
    /**
     * Read last N lines from file efficiently.
     */
    protected function readLinesFromEnd(string $filePath, int $maxLines): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }
        
        // Move to end of file
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);
        
        // If file is small, read normally
        if ($fileSize < 1024 * 1024) { // Less than 1MB
            fseek($handle, 0);
            $content = stream_get_contents($handle);
            fclose($handle);
            $lines = explode("\n", $content);
            return array_slice($lines, -$maxLines);
        }
        
        // Read backwards in chunks
        $lines = [];
        $chunkSize = 8192; // 8KB chunks
        $position = $fileSize;
        $buffer = '';
        
        while ($position > 0 && count($lines) < $maxLines) {
            $readSize = min($chunkSize, $position);
            $position -= $readSize;
            
            fseek($handle, $position);
            $chunk = fread($handle, $readSize);
            $buffer = $chunk . $buffer;
            
            // Split by newlines
            $chunkLines = explode("\n", $buffer);
            
            // Keep last line as it might be incomplete
            $buffer = array_pop($chunkLines);
            
            // Add lines in reverse order
            $lines = array_merge(array_reverse($chunkLines), $lines);
            
            // Limit to maxLines
            if (count($lines) > $maxLines) {
                $lines = array_slice($lines, -$maxLines);
                break;
            }
        }
        
        // Add remaining buffer if any
        if (!empty($buffer)) {
            array_unshift($lines, $buffer);
        }
        
        fclose($handle);
        
        // Return last N lines, reversed to maintain chronological order
        return array_reverse(array_slice($lines, -$maxLines));
    }
    
    /**
     * Read first N lines from file.
     */
    protected function readLinesFromStart(string $filePath, int $maxLines): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }
        
        $lines = [];
        $lineCount = 0;
        
        while ($lineCount < $maxLines && ($line = fgets($handle)) !== false) {
            $lines[] = rtrim($line, "\r\n");
            $lineCount++;
        }
        
        fclose($handle);
        
        return $lines;
    }
    
    /**
     * Create log entry from matches.
     */
    protected function createLogEntry(array $matches, string $filePath): array
    {
        $datetime = Carbon::createFromFormat(
            config('system-logs.parsing.date_format'),
            $matches['datetime']
        );
        
        $body = $matches['body'] ?? '';
        $context = [];
        $message = $body;
        
        // Try to extract JSON context
        if (preg_match('/^(.+?)\s*(\{.*\})$/', $body, $contextMatches)) {
            $message = $contextMatches[1];
            $jsonContext = json_decode($contextMatches[2], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $context = $jsonContext;
            }
        }
        
        return [
            'timestamp' => $datetime,
            'level' => strtolower($matches['level'] ?? 'INFO'),
            'environment' => $matches['environment'] ?? 'local',
            'message' => trim($message),
            'context' => $context,
            'file' => $filePath,
            'raw' => $body,
        ];
    }
    
    /**
     * Apply filters to entries.
     */
    protected function applyFilters(Collection $entries, array $filters): Collection
    {
        return $entries
            ->when(isset($filters['level']), function ($collection) use ($filters) {
                return $collection->where('level', strtolower($filters['level']));
            })
            ->when(isset($filters['environment']), function ($collection) use ($filters) {
                return $collection->where('environment', $filters['environment']);
            })
            ->when(isset($filters['file']), function ($collection) use ($filters) {
                return $collection->where('file', $filters['file']);
            })
            ->when(isset($filters['date']), function ($collection) use ($filters) {
                $date = Carbon::parse($filters['date']);
                return $collection->filter(function ($entry) use ($date) {
                    return $entry['timestamp']->isSameDay($date);
                });
            })
            ->when(isset($filters['search']) && !empty($filters['search']), function ($collection) use ($filters) {
                $search = strtolower($filters['search']);
                return $collection->filter(function ($entry) use ($search) {
                    return Str::contains(strtolower($entry['message']), $search) ||
                           Str::contains(strtolower(json_encode($entry['context'])), $search);
                });
            });
    }
    
    /**
     * Delete a single log entry.
     */
    public function deleteEntry(string $fileName, string $timestamp): bool
    {
        // Handle both relative paths (subfolder/file.log) and simple filenames
        $filePath = $this->logDirectory . DIRECTORY_SEPARATOR . $fileName;
        
        // Security: Ensure the file is within the log directory
        $realPath = realpath($filePath);
        $realLogDir = realpath($this->logDirectory);
        
        if (!$realPath || !$realLogDir || !str_starts_with($realPath, $realLogDir)) {
            return false;
        }
        
        if (!File::exists($realPath)) {
            return false;
        }
        
        $content = File::get($realPath);
        $lines = explode("\n", $content);
        $pattern = config('system-logs.parsing.entry_pattern');
        $targetTimestamp = Carbon::parse($timestamp);
        $newLines = [];
        $skipEntry = false;
        $entryStartIndex = null;
        
        foreach ($lines as $index => $line) {
            if (preg_match($pattern, $line, $matches)) {
                // Check if this is the entry to delete
                $entryTimestamp = Carbon::createFromFormat(
                    config('system-logs.parsing.date_format'),
                    $matches['datetime']
                );
                
                if ($entryTimestamp->equalTo($targetTimestamp)) {
                    $skipEntry = true;
                    $entryStartIndex = $index;
                    continue;
                } else {
                    // Save previous entry if it wasn't skipped
                    if (!$skipEntry && $entryStartIndex !== null) {
                        for ($i = $entryStartIndex; $i < $index; $i++) {
                            $newLines[] = $lines[$i];
                        }
                    }
                    $skipEntry = false;
                    $entryStartIndex = $index;
                }
            }
            
            if (!$skipEntry) {
                if ($entryStartIndex === null || $index > $entryStartIndex) {
                    // This is continuation of current entry or new entry
                    if ($entryStartIndex === null || preg_match($pattern, $line)) {
                        $newLines[] = $line;
                        if (preg_match($pattern, $line)) {
                            $entryStartIndex = $index;
                        }
                    }
                }
            }
        }
        
        // Save remaining lines if last entry wasn't deleted
        if (!$skipEntry && $entryStartIndex !== null) {
            for ($i = $entryStartIndex; $i < count($lines); $i++) {
                if (!isset($newLines[$i])) {
                    $newLines[] = $lines[$i];
                }
            }
        }
        
        // Write back to file
        File::put($realPath, implode("\n", $newLines));
        
        return true;
    }
    
    /**
     * Bulk delete entries by filters.
     */
    public function bulkDeleteByFilters(array $filters): array
    {
        $deletedCount = 0;
        $failedCount = 0;
        
        // Get all matching entries
        $entries = $this->getEntries($filters, PHP_INT_MAX);
        
        // Group entries by file for efficient deletion
        $entriesByFile = $entries['entries']->groupBy('file');
        
        foreach ($entriesByFile as $fileName => $fileEntries) {
            // Delete entries in reverse order to maintain file integrity
            $fileEntries = $fileEntries->sortByDesc(fn($entry) => $entry['timestamp']->timestamp);
            
            foreach ($fileEntries as $entry) {
                $deleted = $this->deleteEntry($fileName, $entry['timestamp']->toIso8601String());
                if ($deleted) {
                    $deletedCount++;
                } else {
                    $failedCount++;
                }
            }
        }
        
        return [
            'deleted' => $deletedCount,
            'failed' => $failedCount,
            'total_matched' => $entries['entries']->count(),
        ];
    }
    
    /**
     * Validate log file.
     */
    protected function isValidLogFile(string $filePath): bool
    {
        $allowedExtensions = config('system-logs.security.allowed_file_extensions', ['.log']);
        $maxSize = config('system-logs.security.max_file_size', 100 * 1024 * 1024);
        
        // Check extension
        $extension = '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }
        
        // Check file size
        if (File::exists($filePath) && File::size($filePath) > $maxSize) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
