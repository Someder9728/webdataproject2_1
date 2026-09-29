<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && in_array($user->u_role, ['admin', 'tenant'], true);
    }

    public function view(User $user, Invoice $invoice): Response
    {
        if (! $user->is_active) {
            return Response::deny();
        }

        if ($user->u_role === 'admin') {
            return Response::allow();
        }

        if (
            $user->u_role === 'tenant' &&
            $user->tenants_t_id !== null &&
            $invoice->rental()
                ->where('tenants_t_id', $user->tenants_t_id)
                ->exists()
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function submitPayment(User $user, Invoice $invoice): Response
    {
        if (! $user->is_active || $user->must_change_password) {
            return Response::deny();
        }

        // Admin รับเงินผ่าน Walk-in แยกจากการส่งหลักฐานของ Tenant
        if ($user->u_role !== 'tenant') {
            return Response::deny();
        }

        if (
            $user->tenants_t_id !== null &&
            $invoice->rental()
                ->where('tenants_t_id', $user->tenants_t_id)
                ->exists()
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function viewPaymentHistory(User $user, Invoice $invoice): Response
    {
        if ($user->must_change_password) {
            return Response::deny();
        }

        return $this->view($user, $invoice);
    }

    public function viewPaymentProof(User $user, Invoice $invoice): Response
    {
        if ($user->must_change_password) {
            return Response::deny();
        }

        return $this->view($user, $invoice);
    }
}