<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Appointment;
use App\Models\Schedule;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'asistente']);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id,deleted_at,NULL'],
            'doctor_id' => ['required', 'exists:doctors,id,activo,1'],
            'schedule_id' => [
                'required', 
                'exists:schedules,id',
                function ($attribute, $value, $fail) {
                    $schedule = Schedule::find($value);
                    $doctorId = $this->input('doctor_id');
                    if ($schedule && $schedule->doctor_id != $doctorId) {
                        $fail('El horario seleccionado no pertenece al médico indicado.');
                    }
                }
            ],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => [
                'required', 
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $schedule = Schedule::find($this->input('schedule_id'));
                    if ($schedule) {
                        $horaInicio = substr($schedule->hora_inicio, 0, 5);
                        $horaFin = substr($schedule->hora_fin, 0, 5);
                        if ($value < $horaInicio || $value >= $horaFin) {
                            $fail("La hora de inicio debe estar dentro del bloque del horario médico ({$horaInicio} - {$horaFin}).");
                        }
                    }
                }
            ],
            'motivo_consulta' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'El paciente es requerido.',
            'patient_id.exists' => 'El paciente no existe o ha sido eliminado.',
            'doctor_id.required' => 'El médico es requerido.',
            'doctor_id.exists' => 'El médico no existe o no está activo en este momento.',
            'schedule_id.required' => 'El horario es requerido.',
            'fecha.required' => 'La fecha es requerida.',
            'fecha.date' => 'La fecha no tiene un formato válido.',
            'fecha.after_or_equal' => 'La fecha no puede estar en el pasado.',
            'hora_inicio.required' => 'La hora de inicio es requerida.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->any()) {
                $exists = Appointment::where('doctor_id', $this->doctor_id)
                    ->where('fecha', $this->fecha)
                    // Consider db saves format H:i:s
                    ->where('hora_inicio', $this->hora_inicio . ':00')
                    ->exists();

                if ($exists) {
                    throw new HttpResponseException(
                        response()->json([
                            'message' => 'El médico ya tiene una cita agendada en ese horario.',
                            'errors' => [
                                'conflicto' => ['El médico ya tiene una cita agendada en ese horario.']
                            ]
                        ], 409)
                    );
                }
            }
        });
    }
}
