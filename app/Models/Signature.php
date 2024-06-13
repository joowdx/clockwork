<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use LSNepomuceno\LaravelA1PdfSign\Exceptions\ProcessRunTimeException;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;
use SensitiveParameter;

class Signature extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'specimen',
        'certificate',
        'password',
        'signaturable_id',
        'signaturable_type',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $signature) {
            // if (file_exists($file = storage_path('app/'.$signature->specimen))) {
            //     unlink($file);
            // }

            // if (file_exists($file = storage_path('app/'.$signature->certificate))) {
            //     unlink($file);
            // }
        });
    }

    public function signaturable()
    {
        return $this->morphTo();
    }

    public function dimension(): Attribute
    {
        return Attribute::make(
            function () {
                if (file_exists($file = storage_path('app/'.$this->specimen))) {
                    [$x, $y] = getimagesize($file);

                    return [$x, $y];
                }

                throw new InvalidArgumentException('File not found.');
            }
        )->shouldCache();
    }

    public function landscape(): Attribute
    {
        return Attribute::make(
            function () {
                [$x, $y] = $this->dimension;

                return $x > $y;
            }
        )->shouldCache();
    }

    public function portrait(): Attribute
    {
        return Attribute::make(
            function () {
                [$x, $y] = $this->dimension;

                return $y > $x;
            }
        )->shouldCache();
    }

    public function specimenBase64(): Attribute
    {
        return Attribute::make(
            function () {
                if (file_exists($file = storage_path('app/'.$this->specimen))) {
                    return base64_encode(file_get_contents($file));
                }

                throw new InvalidArgumentException('File not found.');
            }
        )->shouldCache();
    }

    public function verify(#[SensitiveParameter] string $password): bool
    {
        try {
            (new ManageCert)->setPreservePfx()->fromPfx(storage_path('app/'.$this->certificate), $password);

            return true;
        } catch (ProcessRunTimeException $exception) {
            if (str($exception->getMessage())->contains('password')) {
                return false;
            }

            throw $exception;
        }
    }
}
