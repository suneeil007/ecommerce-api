<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'provider' => $this->provider,

            'transaction_id' => $this->transaction_id,

            'status' => $this->status?->value,

            'amount' => (float) $this->amount,

            'currency' => $this->currency,

            'response_data' => $this->response_data,

            'failure_reason' => $this->failure_reason,

            'processed_at' => $this->processed_at?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

