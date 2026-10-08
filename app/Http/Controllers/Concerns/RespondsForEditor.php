<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Helper respons bersama untuk controller modul editor (DRY).
 */
trait RespondsForEditor
{
    /**
     * Halaman tabel penuh untuk request biasa, JSON {html, links, total} untuk AJAX
     * (live search, filter, pagination) — satu pola untuk semua DataTable.
     */
    protected function tableResponse(
        Request $request,
        string $pageView,
        string $rowsView,
        LengthAwarePaginator $rows,
        array $pageData = []
    ): View|JsonResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'html'  => view($rowsView, ['rows' => $rows])->render(),
                'links' => (string) $rows->links(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ]);
        }

        return view($pageView, ['rows' => $rows] + $pageData);
    }

    /**
     * Jalankan aksi bisnis; DomainException dari service menjadi respons 422 yang ramah.
     */
    protected function perform(Closure $action, string $successMessage, array $extra = []): JsonResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => $successMessage] + $extra);
    }
}
