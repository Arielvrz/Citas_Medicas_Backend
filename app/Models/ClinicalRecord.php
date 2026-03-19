<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClinicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'tipo_sangre',
        'alergias',
        'enfermedades_cronicas',
        'medicamentos_actuales',
        'notas_medicas',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
