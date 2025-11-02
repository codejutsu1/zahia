<?php

namespace App\Services\Audio;

use App\Enums\AudioFormat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AudioService
{
    public function convertAudio(string $path, AudioFormat $format): string
    {
        $input = $this->resolvePath($path);

        $dir = pathinfo($input, PATHINFO_DIRNAME);
        $basename = pathinfo($input, PATHINFO_FILENAME);
        $ext = $format->value;
        $output = $dir.DIRECTORY_SEPARATOR.$basename.'.'.$ext;

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ffmpeg = config('services.ffmpeg.path', 'C:\\ffmpeg\\bin\\ffmpeg.exe');

        return match ($format) {
            AudioFormat::MP3 => $this->convertWithProcess($ffmpeg, $input, $output, ['-acodec', 'libmp3lame', '-q:a', '4']),
            AudioFormat::WAV => $this->convertWithProcess($ffmpeg, $input, $output, ['-ar', '16000', '-ac', '1', '-c:a', 'pcm_s16le']),
            AudioFormat::OPUS => $this->convertWithProcess($ffmpeg, $input, $output, ['-c:a', 'libopus', '-b:a', '32k']),
            AudioFormat::AAC => $this->convertWithProcess($ffmpeg, $input, $output, ['-c:a', 'aac', '-b:a', '96k']),
            AudioFormat::FLAC => $this->convertWithProcess($ffmpeg, $input, $output, ['-c:a', 'flac']),
            AudioFormat::PCM => $this->convertWithProcess($ffmpeg, $input, $output, ['-f', 's16le', '-acodec', 'pcm_s16le', '-ar', '16000', '-ac', '1']),
        };
    }

    protected function resolvePath(string $path): string
    {
        if (file_exists($path)) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        throw new \RuntimeException("Input file not found: {$path}");
    }

    protected function convertWithProcess(
        string $ffmpegFullPath,
        string $input,
        string $output,
        array $extraArgs = []
    ): string {
        if (! file_exists($ffmpegFullPath)) {
            throw new \RuntimeException("ffmpeg binary not found at: {$ffmpegFullPath}");
        }

        $cmd = array_merge(
            [$ffmpegFullPath, '-y', '-i', $input],
            $extraArgs,
            [$output]
        );

        $process = new Process($cmd);
        $process->setTimeout(60);

        $process->run();

        if (! $process->isSuccessful()) {
            $err = $process->getErrorOutput() ?: $process->getOutput();
            Log::error('FFmpeg failed', ['cmd' => $cmd, 'error' => $err]);
            throw new ProcessFailedException($process);
        }

        if (! file_exists($output) || filesize($output) === 0) {
            throw new \RuntimeException("FFmpeg reported success but output file is missing or empty: {$output}");
        }

        Log::info('Audio converted', ['input' => $input, 'output' => $output]);

        return $this->toStorageRelativePath($output);
    }

    protected function toStorageRelativePath(string $absolutePath): string
    {
        $publicRoot = Storage::disk('public')->path('');

        return ltrim(str_replace($publicRoot, '', $absolutePath), DIRECTORY_SEPARATOR);
    }
}
