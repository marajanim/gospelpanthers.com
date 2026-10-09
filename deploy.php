#!/usr/bin/env php
<?php
/** CLI-only deployment of GOPA code. Run with the hosting PHP 8.2 interpreter. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');
$repo = __DIR__;
$target = dirname($repo) . '/gospelpanthers.com';
$statePath = $repo . '/.gopa-deploy-state.json';
$lock = fopen($repo . '/.gopa-deploy.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { exit; }
function command(array $args, string $cwd): string {
    $process = proc_open($args, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    if (!is_resource($process)) { throw new RuntimeException('Cannot run deployment command.'); }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { throw new RuntimeException(trim($error . "\n" . $output)); }
    return trim($output);
}
function allowed(string $path): bool {
    return !str_contains($path, '..') && !str_contains($path, '\\') &&
        ($path === 'wp-content/mu-plugins/gopa-content.php' || str_starts_with($path, 'wp-content/themes/gopa/'));
}
function ensureDirectory(string $path): void {
    if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) { throw new RuntimeException('Cannot create directory.'); }
}
function safeDestination(string $target, string $path): string {
    if (!allowed($path)) { throw new RuntimeException('Deployment path is outside GOPA code.'); }
    $current = $target;
    foreach (explode('/', $path) as $part) {
        $current .= '/' . $part;
        if (is_link($current)) { throw new RuntimeException('Refusing deployment through a symbolic link.'); }
    }
    return $current;
}
function atomicCopy(string $source, string $destination): void {
    ensureDirectory(dirname($destination));
    $temporary = $destination . '.gopa-new-' . bin2hex(random_bytes(6));
    if (!copy($source, $temporary)) { throw new RuntimeException('Cannot stage deployment file.'); }
    chmod($temporary, 0644);
    if (!rename($temporary, $destination)) { @unlink($temporary); throw new RuntimeException('Cannot publish deployment file.'); }
}
$changed = [];
$backup = null;
try {
    if (($argv[1] ?? '') !== '--check') {
        if (command(['git', 'config', '--get', 'remote.origin.url'], $repo) !== 'https://github.com/marajanim/gospelpanthers.com.git') {
            throw new RuntimeException('Unexpected repository remote.');
        }
        if (command(['git', 'branch', '--show-current'], $repo) !== 'main') { throw new RuntimeException('Production requires main.'); }
        command(['git', 'pull', '--ff-only', 'origin', 'main'], $repo);
    }
    $commit = command(['git', 'rev-parse', 'HEAD'], $repo);
    $files = array_values(array_filter(explode("\n", command(['git', 'ls-files'], $repo)), 'allowed'));
    if (!in_array('wp-content/mu-plugins/gopa-content.php', $files, true) || !in_array('wp-content/themes/gopa/style.css', $files, true)) {
        throw new RuntimeException('Required GOPA source files are missing.');
    }
    foreach ($files as $file) {
        if (str_ends_with($file, '.php')) { command([PHP_BINARY, '-l', $repo . '/' . $file], $repo); }
    }
    if (($argv[1] ?? '') === '--check') { echo "GOPA source validation passed.\n"; exit; }
    $realTarget = realpath($target);
    if (!$realTarget || is_link($target) || !is_file($realTarget . '/wp-config.php')) { throw new RuntimeException('Expected production WordPress directory not found.'); }
    $target = $realTarget;
    $previous = is_file($statePath) ? json_decode(file_get_contents($statePath), true, 512, JSON_THROW_ON_ERROR) : [];
    if (($previous['commit'] ?? '') === $commit) { exit; }
    $removed = array_values(array_filter(array_diff($previous['files'] ?? [], $files), 'allowed'));
    $backup = dirname($repo) . '/gopa-deploy-backups/' . gmdate('Ymd-His') . '-' . substr($commit, 0, 12);
    ensureDirectory($backup);
    foreach (array_merge($files, $removed) as $file) {
        $destination = safeDestination($target, $file);
        if (is_file($destination)) { atomicCopy($destination, $backup . '/' . $file); }
    }
    foreach ($files as $file) {
        $destination = safeDestination($target, $file);
        atomicCopy($repo . '/' . $file, $destination);
        $changed[] = $file;
    }
    foreach ($removed as $file) {
        $destination = safeDestination($target, $file);
        if (is_file($destination) && !unlink($destination)) { throw new RuntimeException('Cannot remove an obsolete GOPA file.'); }
        $changed[] = $file;
    }
    $state = ['commit' => $commit, 'deployed_at' => gmdate('c'), 'files' => $files];
    $stateTemporary = $statePath . '.new';
    if (file_put_contents($stateTemporary, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false || !rename($stateTemporary, $statePath)) {
        throw new RuntimeException('Cannot save deployment state.');
    }
    file_put_contents($target . '/wp-content/themes/gopa/deployment.json', json_encode(['commit' => $commit, 'deployed_at' => $state['deployed_at']], JSON_PRETTY_PRINT));
    echo 'Deployed GOPA commit ' . $commit . "\n";
} catch (Throwable $error) {
    foreach (array_reverse($changed) as $file) {
        $destination = safeDestination($target, $file);
        if ($backup && is_file($backup . '/' . $file)) { atomicCopy($backup . '/' . $file, $destination); }
        elseif (is_file($destination)) { unlink($destination); }
    }
    fwrite(STDERR, 'GOPA deployment failed: ' . $error->getMessage() . "\n");
    exit(1);
} finally { flock($lock, LOCK_UN); fclose($lock); }
