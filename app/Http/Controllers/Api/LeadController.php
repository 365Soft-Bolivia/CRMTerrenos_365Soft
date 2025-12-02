<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Negocio;
use App\Models\Terreno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LeadController extends Controller
{
    /**
     * Listar todos los leads
     * GET /api/leads
     */
    /**
     * Listar todos los leads (Vista Web)
     */
    public function webIndex(Request $request)
    {
        $query = Lead::with(['asesor', 'negocio.terreno'])
            ->where('estado', true);

        // Filtrar por asesor si se proporciona
        if ($request->has('asesor_id')) {
            $query->where('asesor_id', $request->asesor_id);
        }

        // Búsqueda por término
        if ($request->has('buscar')) {
            $query->buscar($request->buscar);
        }

        // Paginación
        $leads = $query->latest()->paginate(50);

        return \Inertia\Inertia::render('Leads/LeadList', [
            'initialLeads' => $leads
        ]);
    }

    /**
     * Vista: Crear Lead (Inertia)
     */
    public function webCreate()
    {
        $terrenos = Terreno::disponibles()
            ->select('id', 'ubicacion', 'numero_terreno', 'precio_venta')
            ->orderBy('ubicacion')
            ->get()
            ->map(function ($terreno) {
                return [
                    'id' => $terreno->id,
                    'label' => $terreno->ubicacion . ($terreno->numero_terreno ? ' - Nº ' . $terreno->numero_terreno : ''),
                    'precio' => $terreno->precio_venta,
                ];
            });

        return Inertia::render('Leads/LeadForm', [
            'terrenos' => $terrenos,
        ]);
    }

    /**
     * Vista: Ver detalle de Lead (Inertia)
     */
    public function webShow($id)
    {
        $lead = Lead::with([
            'asesor',
            'negocio.terreno.proyecto',
            'negocio.terreno.categoria',
            'negocio.terreno.cuadra.barrio',
            'negocio.seguimientos.asesor',
        ])->findOrFail($id);

        return Inertia::render('Leads/LeadDetail', [
            'lead' => $lead,
        ]);
    }

    /**
     * Vista: Editar Lead (Inertia)
     */
    public function webEdit($id)
    {
        $lead = Lead::with(['asesor', 'negocio'])->findOrFail($id);

        $terrenos = Terreno::disponibles()
            ->select('id', 'ubicacion', 'numero_terreno', 'precio_venta')
            ->orderBy('ubicacion')
            ->get()
            ->map(function ($terreno) {
                return [
                    'id' => $terreno->id,
                    'label' => $terreno->ubicacion . ($terreno->numero_terreno ? ' - Nº ' . $terreno->numero_terreno : ''),
                    'precio' => $terreno->precio_venta,
                ];
            });

        return Inertia::render('Leads/LeadForm', [
            'lead' => $lead,
            'terrenos' => $terrenos,
        ]);
    }

    /**
     * Listar todos los leads (API JSON)
     * GET /api/leads
     */
    public function index(Request $request)
    {
        try {
            $query = Lead::with(['asesor', 'negocio.terreno'])
                ->where('estado', true);

            // Filtrar por asesor si se proporciona
            if ($request->has('asesor_id')) {
                $query->where('asesor_id', $request->asesor_id);
            }

            // Búsqueda por término
            if ($request->has('buscar')) {
                $query->buscar($request->buscar);
            }

            // Paginación
            $perPage = $request->get('per_page', 50);
            $leads = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $leads
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los leads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo lead
     * POST /api/leads
     */
    public function store(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'carnet' => 'required|string|max:50|unique:leads,carnet',
            'numero_1' => 'required|string|max:20',
            'numero_2' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'crear_acuerdo' => 'nullable|boolean',
            'convertir_automatico' => 'nullable|boolean',
            // Campos del negocio (si crear_acuerdo = true)
            'terreno_id' => 'nullable|required_if:crear_acuerdo,true|exists:terrenos,id',
            'etapa' => 'required_if:crear_acuerdo,true|string|max:100',
            'fecha_inicio' => 'required_if:crear_acuerdo,true|date',
            'monto_estimado' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'carnet.required' => 'El carnet/CI es obligatorio',
            'carnet.unique' => 'Este carnet/CI ya está registrado',
            'numero_1.required' => 'El número de teléfono principal es obligatorio',
            'terreno_id.required_if' => 'Debe seleccionar un terreno',
            'terreno_id.exists' => 'El terreno seleccionado no existe',
            'etapa.required_if' => 'Debe seleccionar una etapa',
            'fecha_inicio.required_if' => 'La fecha de inicio es obligatoria',
        ]);

        if ($validator->fails()) {
            // Si viene desde Inertia, redirigir con errores en lugar de JSON
            if ($request->header('X-Inertia')) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Crear el lead
            $lead = Lead::create([
                'nombre' => $request->nombre,
                'carnet' => $request->carnet,
                'numero_1' => $request->numero_1,
                'numero_2' => $request->numero_2,
                'direccion' => $request->direccion,
                'asesor_id' => Auth::id(), // Auto-asignar al usuario autenticado
            ]);

            // Si se marca crear acuerdo, crear el negocio
            $negocio = null;
            if ($request->crear_acuerdo) {
                $negocio = Negocio::create([
                    'lead_id' => $lead->id,
                    'terreno_id' => $request->terreno_id,
                    'tipo_operacion' => 'ventas',
                    'embudo' => 'ventas',
                    'etapa' => $request->etapa,
                    'fecha_inicio' => $request->fecha_inicio,
                    'monto_estimado' => $request->monto_estimado,
                    'notas' => $request->notas,
                    'asesor_id' => Auth::id(),
                    'convertido_cliente' => false,
                ]);
            }

            DB::commit();

            // Cargar relaciones
            $lead->load(['asesor', 'negocio.terreno']);

            // Si viene desde Inertia, redirigir a la lista con mensaje flash
            if ($request->header('X-Inertia')) {
                return redirect()
                    ->route('leads.index')
                    ->with('success', 'Lead creado exitosamente');
            }

            return response()->json([
                'success' => true,
                'message' => 'Lead creado exitosamente',
                'data' => $lead
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el lead',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver detalle de un lead
     * GET /api/leads/{id}
     */
    public function show($id)
    {
        try {
            $lead = Lead::with([
                'asesor',
                'negocio.terreno.proyecto',
                'negocio.terreno.categoria',
                'negocio.terreno.cuadra.barrio',
                'negocio.seguimientos.asesor'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $lead
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar un lead
     * PUT /api/leads/{id}
     */
    public function update(Request $request, $id)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'carnet' => 'required|string|max:50|unique:leads,carnet,' . $id,
            'numero_1' => 'required|string|max:20',
            'numero_2' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'carnet.required' => 'El carnet/CI es obligatorio',
            'carnet.unique' => 'Este carnet/CI ya está registrado',
            'numero_1.required' => 'El número de teléfono principal es obligatorio',
        ]);

        if ($validator->fails()) {
            // Si viene desde Inertia, redirigir con errores en lugar de JSON
            if ($request->header('X-Inertia')) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $lead = Lead::findOrFail($id);

            $lead->update([
                'nombre' => $request->nombre,
                'carnet' => $request->carnet,
                'numero_1' => $request->numero_1,
                'numero_2' => $request->numero_2,
                'direccion' => $request->direccion,
            ]);

            $lead->load(['asesor', 'negocio.terreno']);

            // Si viene desde Inertia, redirigir a la lista con mensaje flash
            if ($request->header('X-Inertia')) {
                return redirect()
                    ->route('leads.index')
                    ->with('success', 'Lead actualizado exitosamente');
            }

            return response()->json([
                'success' => true,
                'message' => 'Lead actualizado exitosamente',
                'data' => $lead,
            ], 200);

        } catch (\Exception $e) {
            if ($request->header('X-Inertia')) {
                return redirect()
                    ->back()
                    ->withErrors(['error' => $e->getMessage()])
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el lead',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}