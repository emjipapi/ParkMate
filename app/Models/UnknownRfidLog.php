<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnknownRfidLog extends Model
{
    /**
     * Table backing this model.
     *
     * @var string
     */
    protected $table = 'unknown_rfid_logs';

    /**
     * Mass assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'rfid_tag',
        'area_id',
        'created_at',
    ];

    /**
     * We only have created_at in the table; disable updated_at.
     *
     * @var bool
     */
    public $timestamps = true;
    const UPDATED_AT = null; // prevents Eloquent trying to set updated_at

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'area_id'    => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Optional relation to Area (if you have an Area model).
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(ParkingArea::class, 'area_id');
    }
}
