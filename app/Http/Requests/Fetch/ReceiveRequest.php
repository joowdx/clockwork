<?php

namespace App\Http\Requests\Fetch;

use App\Models\Scanner;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReceiveRequest extends FormRequest
{
    const MAX_DATA_SIZE = 1024 * 1024 * 12;

    protected ?array $payload = null;

    protected ?array $timelogs = null;

    protected ?User $user = null;

    protected ?Scanner $scanner = null;

    protected ?Carbon $month = null;

    public function rules(): array
    {
        return [
            'status' => 'required|string',
            'message' => 'required_unless:status,success|string',
            'data' => 'required_if:status,success|json|ascii|max:'.self::MAX_DATA_SIZE,
        ];
    }

    public function messages()
    {
        return [
            'data.max' => 'The :attribute may not be greater than '.Number::fileSize(self::MAX_DATA_SIZE).'.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $data = json_decode($this->input('data'), true);

                if (json_last_error() !== JSON_ERROR_NONE || $this->input('status') !== 'success' || empty($data)) {
                    return;
                }

                $this->payload = $data;

                $validation = \Illuminate\Support\Facades\Validator::make(compact('data'), [
                    'data.timelogs.*.uid' => 'required',
                    'data.timelogs.*.state' => 'required|numeric|min:0',
                    'data.timelogs.*.mode' => 'required|numeric|min:0',
                    'data.timelogs.*.time' => 'required|date_format:Y-m-d H:i:s',
                    'data.timelogs' => 'required|array',
                    'data.month' => 'required|date_format:Y-m',
                    'data.host' => ['required', function ($attribute, $value, $fail) {
                        try {
                            $this->scanner = Scanner::where('host', $value)->firstOrFail();
                        } catch (NotFoundHttpException) {
                            $fail('The selected :attribute is invalid.');
                        }
                    }],
                    'data.user' => ['required', function ($attribute, $value, $fail) {
                        try {
                            $this->user = User::findOrFail(decrypt($value));
                        } catch (DecryptException|NotFoundHttpException) {
                            $fail('The selected :attribute is invalid.');
                        }
                    }],
                ]);

                if ($validation->fails()) {
                    $validator->errors()->merge($validation->errors());
                }
            }
        ];
    }

    public function success(): bool
    {
        return $this->input('status') === 'success';
    }

    public function user($guard = null): User
    {
        return is_null($guard) ? $this->user ?? User::findOrFail(decrypt($this->payload['user']) ?? null) : parent::user($guard);
    }

    public function scanner(): Scanner
    {
        return $this->scanner ?? Scanner::where('host', $this->host())->firstOrFail();
    }

    public function month(): Carbon
    {
        return $this->month ?? Carbon::parse($this->payload['month']);
    }

    public function timelogs(): Collection
    {
        return $this->timelogs ?? collect($this->payload['timelogs'])->map(fn ($t) => [...$t, 'device' => $this->scanner()->uid]);
    }

    public function host(): string
    {
        return $this->host ?? $this->payload['host'];
    }

    public function notify()
    {
        $message = $this->success()
            ? <<<HTML
                Timelogs of <i>{$this->scanner()->name}</i> has been successfully fetched from the device <br>
                <i>You may have to wait for a bit before the employees' records are updated</i>
            HTML
            : <<<HTML
                Errors occurred <i>{$this->scanner()->name}</i>: <br>
                {$this->message}
            HTML;


        $notification = Notification::make()
            ->title($this->success() ? 'Fetch successful' : 'Fetch failed')
            ->body($message);

        match ($this->success()) {
            true => $notification->success(),
            default => $notification->error(),
        };

        $notification->sendToDatabase($this->user(), true);
    }

    protected function passedValidation(): void
    {
        $this->payload = json_decode($this->input('data'), true);
    }
}
