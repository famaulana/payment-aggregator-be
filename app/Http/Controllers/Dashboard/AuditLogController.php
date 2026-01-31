<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Services\AuditTrailService;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->user_id,
                'client_id' => $request->client_id,
                'action_type' => $request->action_type,
                'entity_type' => $request->entity_type,
                'entity_id' => $request->entity_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ];

            $perPage = $request->per_page ?? 50;
            $auditLogs = $this->auditService->getAuditLogs($filters, $perPage);

            return $this->pagination(
                paginator: $auditLogs,
                message: __('messages.audit_logs_retrieved')
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.audit_logs_error')
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $auditLog = \App\Models\AuditTrail::with(['user', 'client'])
                ->findOrFail($id);

            return $this->success(
                data: $auditLog,
                message: __('messages.audit_log_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.audit_log_not_found'));
        }
    }
}
