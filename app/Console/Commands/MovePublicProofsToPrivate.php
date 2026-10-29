<?php

namespace App\Console\Commands;

use App\Models\Penalty;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// Moves penalty proof images from the public disk to the private disk.
//
// Before this change, proofs were saved with store('penalties', 'public'),
// so anyone with a /storage/penalties/... link could open them without
// logging in. New uploads already go to the private disk and are only
// served through the gated penalties.proof route.
//
// Run once after deploying that change:
//
//   php artisan penalties:move-proofs-to-private
//
// Use --dry-run first to see what would move, without changing anything.
// A file is only deleted from the public disk after it has been copied
// to the private disk and the copy's size matches, so a failed copy
// never loses the original.
class MovePublicProofsToPrivate extends Command
{
    protected $signature = 'penalties:move-proofs-to-private {--dry-run : List what would move, without moving anything}';

    protected $description = 'Move penalty proof files from the public disk to the private disk';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $local = Storage::disk('local');

        $moved = 0;
        $alreadyPrivate = 0;
        $missing = 0;
        $failed = 0;

        $moveOne = function (string $path, string $label) use ($public, $local, $dryRun, &$moved, &$failed) {
            if (!$public->exists($path)) {
                return;
            }

            if ($local->exists($path)) {
                // Already on the private disk: the public copy is just a
                // duplicate, so it can be removed.
                if ($dryRun) {
                    $this->line("Would delete duplicate public copy: {$path} ({$label})");
                } else {
                    $public->delete($path);
                    $this->line("Deleted duplicate public copy: {$path} ({$label})");
                }
                $moved++;

                return;
            }

            if ($dryRun) {
                $this->line("Would move: {$path} ({$label})");
                $moved++;

                return;
            }

            $contents = $public->get($path);
            $local->put($path, $contents);

            // Only delete the original once the copy is verified.
            if ($local->exists($path) && $local->size($path) === $public->size($path)) {
                $public->delete($path);
                $this->line("Moved: {$path} ({$label})");
                $moved++;
            } else {
                $this->error("Copy failed, public file kept: {$path} ({$label})");
                $failed++;
            }
        };

        foreach (Penalty::whereNotNull('proof_path')->get() as $penalty) {
            $path = $penalty->proof_path;

            if ($local->exists($path)) {
                // Already private: still drop a stray public duplicate.
                $moveOne($path, "penalty #{$penalty->id}");
                $alreadyPrivate++;
                continue;
            }

            if (!$public->exists($path)) {
                $this->warn("Missing on both disks, left as-is: {$path} (penalty #{$penalty->id})");
                $missing++;
                continue;
            }

            $moveOne($path, "penalty #{$penalty->id}");
        }

        // Orphan files: proof images on the public disk that no penalty
        // row references anymore. They are still proof images, so move
        // them too instead of leaving them publicly readable.
        $referenced = Penalty::whereNotNull('proof_path')->pluck('proof_path')->all();
        foreach ($public->files('penalties') as $path) {
            if (!in_array($path, $referenced, true)) {
                $moveOne($path, 'orphan');
            }
        }

        $this->newLine();
        $this->info("Moved/deleted public copies: {$moved}");
        $this->info("Already private: {$alreadyPrivate}");
        $this->info("Missing on both disks: {$missing}");
        $this->info("Failed: {$failed}");

        if ($dryRun) {
            $this->info('Dry run only: nothing was changed.');
        } elseif ($failed === 0) {
            $this->info('Done. Proof files now live on the private disk only.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
