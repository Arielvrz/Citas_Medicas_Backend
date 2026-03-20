<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'hora_inicio' => $this->hora_inicio,
            'estado' => $this->estado,
            'motivo_consulta' => $this->motivo_consulta,
            'paciente' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'nombre_completo' => $this->patient->nombre . ' ' . $this->patient->apellido,
                    'dui' => $this->patient->dui,
                ];
            }),
            'medico' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'especialidad' => $this->doctor->especialidad,
                    'nombre_completo' => clone $this->doctor->user ? clone $this->doctor->user->name : '',
                ];
            }),
            'horario' => $this->whenLoaded('schedule', function () {
                return [
                    'dia_semana' => $this->schedule->dia_semana,
                    'hora_inicio' => $this->schedule->hora_inicio,
                    'hora_fin' => $this->schedule->hora_fin,
                ];
            }),
        ];
    }
}
