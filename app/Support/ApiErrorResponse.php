<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApiErrorResponse
{
    public static function validation(ValidationException $exception): JsonResponse
    {
        $errors = self::validationErrors($exception);
        $firstMessage = collect($errors)->flatten()->first() ?: 'Të dhënat e dërguara nuk janë të vlefshme.';

        return response()->json([
            'message' => $firstMessage,
            'errors' => $errors,
        ], 422);
    }

    public static function conflict(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 409);
    }

    public static function server(Throwable $exception): JsonResponse
    {
        $payload = [
            'message' => 'Ndodhi një gabim në server. Provoni përsëri më vonë.',
        ];

        if (config('app.debug')) {
            $payload['detail'] = $exception->getMessage();
        }

        return response()->json($payload, 500);
    }

    public static function database(QueryException $exception): JsonResponse
    {
        $message = self::databaseMessage($exception);
        $status = str_contains(strtolower($message), 'ekziston') ? 409 : 500;

        return response()->json([
            'message' => $message,
        ], $status);
    }

    private static function validationErrors(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->validator->failed() as $field => $rules) {
            foreach (array_keys($rules) as $rule) {
                $errors[$field][] = self::messageForRule($field, $rule);
            }
        }

        return $errors ?: $exception->errors();
    }

    private static function messageForRule(string $field, string $rule): string
    {
        $rule = strtolower($rule);
        $label = self::label($field);

        if ($rule === 'required') {
            return "{$label} është i detyrueshëm.";
        }

        if ($rule === 'email') {
            return 'Emaili nuk është i vlefshëm.';
        }

        if ($rule === 'unique') {
            return str_contains(strtolower($field), 'email')
                ? 'Ky email është regjistruar tashmë.'
                : "{$label} ekziston tashmë.";
        }

        if ($rule === 'confirmed') {
            return 'Konfirmimi i fjalëkalimit nuk përputhet.';
        }

        if ($rule === 'min') {
            return "{$label} duhet të ketë të paktën 8 karaktere.";
        }

        if ($rule === 'max') {
            return "{$label} është shumë i gjatë.";
        }

        if ($rule === 'in') {
            return "{$label} ka një vlerë të palejuar.";
        }

        if ($rule === 'exists') {
            return "{$label} nuk u gjet në sistem.";
        }

        if ($rule === 'date') {
            return "{$label} duhet të jetë datë e vlefshme.";
        }

        if ($rule === 'integer') {
            return "{$label} duhet të jetë numër.";
        }

        if ($rule === 'string') {
            return "{$label} duhet të jetë tekst.";
        }

        if ($rule === 'regex') {
            if ($field === 'password') {
                return 'Fjalëkalimi duhet të përmbajë të paktën një shkronjë dhe një numër.';
            }

            return "{$label} nuk ka formatin e duhur.";
        }

        return "{$label} nuk është i vlefshëm.";
    }

    private static function databaseMessage(QueryException $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'unique constraint') || str_contains($message, 'duplicate')) {
            if (str_contains($message, 'email')) {
                return 'Ky email është regjistruar tashmë.';
            }

            return 'Ky rekord ekziston tashmë.';
        }

        if (str_contains($message, 'foreign key constraint')) {
            return 'Rekordi i lidhur nuk ekziston ose nuk mund të përdoret.';
        }

        if (str_contains($message, 'check constraint')) {
            return 'Vlera e dërguar nuk lejohet nga sistemi.';
        }

        return 'Ndodhi një gabim gjatë ruajtjes së të dhënave.';
    }

    private static function label(string $field): string
    {
        return [
            'name' => 'Emri',
            'email' => 'Emaili',
            'password' => 'Fjalëkalimi',
            'password_confirmation' => 'Konfirmimi i fjalëkalimit',
            'role' => 'Roli',
            'token' => 'Kodi i verifikimit',
            'sek_id' => 'Seksioni',
            'STD_EM' => 'Emri',
            'STD_MB' => 'Mbiemri',
            'STD_EMAIL' => 'Emaili',
            'STD_DTL' => 'Datëlindja',
            'STD_GJINI' => 'Gjinia',
            'DEP_ID' => 'Departamenti',
        ][$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
}
