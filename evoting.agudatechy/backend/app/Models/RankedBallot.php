<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One voter's ranking in a ranked-choice category, kept so the category can be
 * counted in rounds. It holds nothing that identifies the voter: no voter id,
 * no time, and a random id, so not even the row order follows who voted when.
 */
class RankedBallot extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['category_id', 'ranking'];

    protected $casts = ['ranking' => 'array'];

    /** Fully random (v4), never time-ordered: the id must not reveal when the ballot was cast. */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }
}
