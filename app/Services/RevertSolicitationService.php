<?php

namespace App\Services;

use App\Exceptions\SolicitationNotPendingException;
use App\Models\RevertSolicitations;

class RevertSolicitationService
{
    public function __construct(
        protected RevertSolicitations $solicitation
    ) {}

    public function approve(): void
    {
        $this->validateIsPending();

        $this->solicitation->approve();

        $revertService = new RevertTransfer($this->solicitation->transaction);
        $revertService->makeRevert();
    }

    public function reject(): void
    {
        $this->validateIsPending();

        $this->solicitation->reject();
    }

    public function delete(): void
    {
        $this->validateIsPending();

        $this->solicitation->delete();
    }

    private function validateIsPending(): void
    {
        if (! $this->solicitation->isPending()) {
            throw new SolicitationNotPendingException;
        }
    }
}
