<?php

namespace App\Enums;

enum AudioFormat: string
{
    case MP3 = 'mp3';
    case WAV = 'wav';
    case OPUS = 'opus';
    case AAC = 'aac';
    case FLAC = 'flac';
    case PCM = 'pcm';
}
