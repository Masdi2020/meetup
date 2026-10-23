<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrivatizeBookingResults extends Command
{
    protected $signature = 'booking-results:privatize';

    protected $description = 'Memindahkan hasil rapat dari storage publik ke storage privat';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $count = 0;

        foreach ($public->allFiles('booking-results') as $path) {
            if (! $private->exists($path)) {
                $stream = $public->readStream($path);

                if ($stream === null) {
                    throw new RuntimeException("Gagal membaca {$path}.");
                }

                try {
                    if (! $private->writeStream($path, $stream)) {
                        throw new RuntimeException("Gagal menyalin {$path}.");
                    }
                } finally {
                    fclose($stream);
                }
            }

            $checksum = $public->checksum($path);

            if ($checksum === false || $checksum !== $private->checksum($path)) {
                throw new RuntimeException("Isi file berbeda: {$path}. Salinan publik belum dihapus.");
            }

            if (! $public->delete($path)) {
                throw new RuntimeException("Gagal menghapus salinan publik {$path}.");
            }

            $count++;
        }

        $this->info("{$count} file hasil rapat dipindahkan ke storage privat.");

        return self::SUCCESS;
    }
}
