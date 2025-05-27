<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;

class CustomFieldController extends Controller
{
    public function index(): JsonResponse
    {
        $customFields = CustomField::active()->ordered()->get();
        return response()->json($customFields);
    }

    public function all(): JsonResponse
    {
        $customFields = CustomField::ordered()->get();
        return response()->json($customFields);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:custom_fields,name',
            'label' => 'required|string|max:255',
            'type' => 'required|in:text',
            'is_required' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $customField = CustomField::create([
            'name' => $request->name,
            'label' => $request->label,
            'type' => $request->type,
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return response()->json($customField, 201);
    }

    public function show(CustomField $customField): JsonResponse
    {
        return response()->json($customField);
    }

    public function update(Request $request, CustomField $customField): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:custom_fields,name,' . $customField->id,
            'label' => 'required|string|max:255',
            'type' => 'required|in:text',
            'is_required' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $customField->update([
            'name' => $request->name,
            'label' => $request->label,
            'type' => $request->type,
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $request->sort_order ?? $customField->sort_order,
            'is_active' => $request->boolean('is_active', $customField->is_active),
        ]);

        return response()->json($customField);
    }

    public function destroy(CustomField $customField): JsonResponse
    {
        // Мягкое удаление - деактивируем поле
        $customField->update(['is_active' => false]);
        
        return response()->json(['message' => 'Дополнительное поле деактивировано']);
    }

    public function reactivate(CustomField $customField): JsonResponse
    {
        $customField->update(['is_active' => true]);
        
        return response()->json(['message' => 'Дополнительное поле активировано']);
    }
}
