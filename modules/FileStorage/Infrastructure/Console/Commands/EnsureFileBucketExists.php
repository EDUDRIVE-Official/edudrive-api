<?php

declare(strict_types=1);

namespace Modules\FileStorage\Infrastructure\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

final class EnsureFileBucketExists extends Command
{
    protected $signature = 'files:ensure-bucket';

    protected $description = 'Crea el bucket de almacenamiento de archivos en MinIO/S3 si todavia no existe.';

    public function handle(): int
    {
        $bucket = (string) config('filesystems.disks.s3.bucket');

        if ($bucket === '') {
            $this->error('No hay un bucket configurado en filesystems.disks.s3.bucket.');

            return self::FAILURE;
        }

        $client = new S3Client([
            'version' => 'latest',
            'region' => (string) config('filesystems.disks.s3.region'),
            'endpoint' => config('filesystems.disks.s3.endpoint'),
            'use_path_style_endpoint' => (bool) config('filesystems.disks.s3.use_path_style_endpoint'),
            'credentials' => [
                'key' => (string) config('filesystems.disks.s3.key'),
                'secret' => (string) config('filesystems.disks.s3.secret'),
            ],
        ]);

        if ($client->doesBucketExist($bucket)) {
            $this->info("El bucket \"{$bucket}\" ya existe.");
        } else {
            $client->createBucket(['Bucket' => $bucket]);
            $this->info("Bucket \"{$bucket}\" creado.");
        }

        $client->putBucketVersioning([
            'Bucket' => $bucket,
            'VersioningConfiguration' => ['Status' => 'Enabled'],
        ]);
        $this->info("Versionado de objetos habilitado en \"{$bucket}\".");

        return self::SUCCESS;
    }
}
