<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Events\BackupWasSuccessful;

class HandleBackups
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BackupWasSuccessful $event): void
    {
        $target = basename($event->backupDestination->newestBackup()->path(), '.zip');
        $name = str_replace(
            $target,
            $target . '-' . app('currentTenant')->domain . '-' . config('app')['env'],
            $event->backupDestination->newestBackup()->path()
        );
        $name = str_replace(config('app')['name'], 'db', $name);
        Storage::disk('backups')->move($event->backupDestination->newestBackup()->path(), $name);
        $file = fopen(Storage::disk('backups')->path($name), 'r');
        $botToken = config('backup')['telegram_bot_token'];
        $chatId = config('backup')['telegram_chat_id'];
        $response = Http::attach('document', $file, $name)
            ->post('https://api.telegram.org/bot' . $botToken . '/sendDocument', [
                'chat_id' => $chatId
            ]);
        fclose($file);
        if ($response->successful()) {
            Storage::disk('backups')->delete($name);
        } else {
            Http::post('https://api.telegram.org/bot' . $botToken . '/sendMessage', [
                'chat_id' => $chatId,
                'text' => 'Failed to upload the backup ' . $name
            ]);
        }
    }
}
