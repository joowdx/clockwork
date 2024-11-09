<?php

namespace App\Actions;

use App\Models\Attachment;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SignAccomplishment
{
    public function __invoke(Attachment $attachment, Employee|User $user)
    {
        return $this->sign($attachment, $user);
    }

    public function sign(Attachment $attachment, Employee|User $user)
    {
        DB::transaction(function () use ($attachment, $user) {
            $file = $attachment->content;

            $out = sys_get_temp_dir().'/'.uniqid().'.pdf';

            file_put_contents($out, $file);

            (new SignPdfAction)($user, $out, null, mb_strtolower(str()->ulid()));

            Storage::disk('azure')->put($attachment->filename, file_get_contents($out));
        });
    }
}
