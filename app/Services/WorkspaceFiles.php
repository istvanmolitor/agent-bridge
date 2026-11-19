<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkspaceFiles
{
    /**
     * Maximum file size (bytes) returned by read(). Larger files are truncated.
     */
    protected const MAX_READ_BYTES = 256 * 1024;

    /**
     * List the immediate children of a directory inside the workspace, with
     * directories first and files second, each sorted alphabetically.
     *
     * @return list<array{name: string, path: string, type: 'directory'|'file'}>
     */
    public function children(Workspace $workspace, string $relativePath = ''): array
    {
        $absolute = $this->resolve($workspace, $relativePath);

        if (! is_dir($absolute)) {
            throw new NotFoundHttpException('Directory not found.');
        }

        $root = $this->root($workspace);

        $directories = [];
        $files = [];

        foreach (File::directories($absolute) as $directory) {
            $directories[] = [
                'name' => basename($directory),
                'path' => $this->relativize($root, $directory),
                'type' => 'directory',
            ];
        }

        foreach (File::files($absolute, true) as $file) {
            $files[] = [
                'name' => $file->getFilename(),
                'path' => $this->relativize($root, $file->getPathname()),
                'type' => 'file',
            ];
        }

        $sortByName = static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']);

        usort($directories, $sortByName);
        usort($files, $sortByName);

        return [...$directories, ...$files];
    }

    /**
     * Read a text file inside the workspace.
     *
     * @return array{path: string, content: string, truncated: bool, binary: bool}
     */
    public function read(Workspace $workspace, string $relativePath): array
    {
        $absolute = $this->resolve($workspace, $relativePath);

        if (! is_file($absolute)) {
            throw new NotFoundHttpException('File not found.');
        }

        $root = $this->root($workspace);
        $path = $this->relativize($root, $absolute);

        $raw = (string) file_get_contents($absolute, length: self::MAX_READ_BYTES);

        if ($this->looksBinary($raw)) {
            return [
                'path' => $path,
                'content' => '',
                'truncated' => false,
                'binary' => true,
            ];
        }

        return [
            'path' => $path,
            'content' => $raw,
            'truncated' => filesize($absolute) > self::MAX_READ_BYTES,
            'binary' => false,
        ];
    }

    /**
     * Resolve a workspace-relative path to an absolute path, guaranteeing the
     * result stays within the workspace root (prevents path traversal).
     */
    protected function resolve(Workspace $workspace, string $relativePath): string
    {
        $root = $this->root($workspace);

        $candidate = $relativePath === ''
            ? $root
            : $root.DIRECTORY_SEPARATOR.ltrim($relativePath, DIRECTORY_SEPARATOR);

        $real = realpath($candidate);

        if ($real === false || ! $this->withinRoot($root, $real)) {
            throw new NotFoundHttpException('Path not found.');
        }

        return $real;
    }

    /**
     * Resolve and normalise the workspace root. The directory must exist.
     */
    protected function root(Workspace $workspace): string
    {
        $root = realpath($workspace->path);

        if ($root === false || ! is_dir($root)) {
            throw new NotFoundHttpException('Workspace directory not found.');
        }

        return $root;
    }

    protected function withinRoot(string $root, string $path): bool
    {
        return $path === $root || str_starts_with($path, $root.DIRECTORY_SEPARATOR);
    }

    protected function relativize(string $root, string $absolute): string
    {
        return ltrim(substr($absolute, strlen($root)), DIRECTORY_SEPARATOR);
    }

    /**
     * Heuristic: treat content containing a NUL byte as binary.
     */
    protected function looksBinary(string $content): bool
    {
        return str_contains($content, "\0");
    }
}
