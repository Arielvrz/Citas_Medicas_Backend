<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicalRecord;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Appointment::with(['patient', 'doctor.user', 'schedule']);

        // Doctor solo ve las suyas
        if ($user->hasRole('medico')) {
            $query->where('doctor_id', $user->doctor->id ?? 0);
        } else {
            // Asistente / Admin pueden filtrar
            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }
        }

        return AppointmentResource::collection($query->paginate(15));
    }

    public function store(StoreAppointmentRequest $request)
    {
        $data = $request->validated();
        $data['estado'] = 'pendiente';

        $appointment = Appointment::create($data);

        // Crear automáticamente ClinicalRecord si no existe
        ClinicalRecord::firstOrCreate([
            'patient_id' => $appointment->patient_id,
        ]);

        $appointment->load(['patient', 'doctor.user', 'schedule']);

        return response()->json(new AppointmentResource($appointment), 201);
    }

    public function show(Appointment $appointment)
    {
        Gate::authorize('view', $appointment);

        $appointment->load(['patient', 'doctor.user', 'schedule']);
        return new AppointmentResource($appointment);
    }

    public function update(Request $request, Appointment $appointment)
    {
        Gate::authorize('update', $appointment);

        $request->validate([
            'estado' => ['required', 'in:pendiente,confirmada,completada,cancelada']
        ]);

        $user = $request->user();
        
        if (in_array($request->estado, ['completada', 'cancelada'])) {
            if (!$user->hasRole('admin')) {
                if ($user->hasRole('medico') && $appointment->doctor?->user_id !== $user->id) {
                    return response()->json(['message' => 'No tienes permiso para completar/cancelar esta cita.'], 403);
                }
                if ($user->hasRole('asistente') && $request->estado === 'completada') {
                    return response()->json(['message' => 'Los asistentes no pueden marcar citas como completadas.'], 403);
                }
            }
        }

        $appointment->update(['estado' => $request->estado]);

        return new AppointmentResource($appointment->load(['patient', 'doctor.user', 'schedule']));
    }
}
