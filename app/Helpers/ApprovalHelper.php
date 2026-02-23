<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class ApprovalHelper
{
    public static function attach(object $model, string $processType): object
    {
        $model->approval_status = null;
        $model->current_step    = null;
        $model->request_step_id = null;
        $model->progress        = null;
        $workflowRequest = DB::table('htapp_workflow_requests')
            ->where('process_type', $processType)
            ->where('process_id', $model->id)
            ->latest()
            ->first();
        if (!$workflowRequest) {
            return $model;
        }
        $currentStep = DB::table('htapp_workflow_request_steps')
            ->where('workflow_request_id', $workflowRequest->id)
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->orderBy('step_order')
            ->first();
        $totalSteps = DB::table('htapp_workflow_request_steps')
            ->where('workflow_request_id', $workflowRequest->id)
            ->count();
        $approvedSteps = DB::table('htapp_workflow_request_steps')
            ->where('workflow_request_id', $workflowRequest->id)
            ->where('status', 'APPROVED')
            ->count();
        $lastApprovedStep = DB::table('htapp_workflow_request_steps')
            ->where('workflow_request_id', $workflowRequest->id)
            ->where('status', 'APPROVED')
            ->orderBy('step_order', 'desc')
            ->first();
        $model->approval_status = $lastApprovedStep
            ? $lastApprovedStep->message
            : 'Initiated';
        $model->current_step    = $currentStep->title ?? null;
        $model->request_step_id = $currentStep->id ?? null;
        $model->progress        = $totalSteps > 0
            ? "{$approvedSteps}/{$totalSteps}"
            : null;
        return $model;
    }
}
