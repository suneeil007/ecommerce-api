<?php

namespace App\Modules\Cart\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Cart\Requests\StoreCartItemRequest;
use App\Modules\Cart\Requests\UpdateCartItemRequest;
use App\Modules\Cart\Resources\CartResource;
use App\Modules\Cart\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Get Cart
    |--------------------------------------------------------------------------
    |
    | GET /api/cart
    |
    */

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            /*
            |--------------------------------------------------------------------------
            | Authenticated User
            |--------------------------------------------------------------------------
            */

            if ($user) {
                $cart = $this->cartService->getCart(
                    user: $user
                );

                return ApiResponse::success(
                    new CartResource($cart),
                    'Cart retrieved successfully.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Guest User
            |--------------------------------------------------------------------------
            */

            $sessionId = $this->getOrCreateGuestSessionId(
                $request
            );

            $cart = $this->cartService->getCart(
                sessionId: $sessionId
            );

            return $this->cartResponse(
                $cart,
                'Cart retrieved successfully.',
                $sessionId
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Add Cart Item
    |--------------------------------------------------------------------------
    |
    | POST /api/cart/items
    |
    */

    public function store(
        StoreCartItemRequest $request
    ): JsonResponse {
        try {
            $user = $this->getAuthenticatedUser(
                $request
            );

            $sessionId = null;

            /*
            |--------------------------------------------------------------------------
            | Guest Session
            |--------------------------------------------------------------------------
            */

            if (!$user) {
                $sessionId = $this->getOrCreateGuestSessionId(
                    $request
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Add Item
            |--------------------------------------------------------------------------
            */

            $cart = $this->cartService->addItem(
                variantUuid: $request->validated('product_variant_id'),
                quantity: (int) $request->validated('quantity'),
                user: $user,
                sessionId: $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return $this->cartResponse(
                $cart,
                'Item added to cart successfully.',
                $sessionId,
                201
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Cart Item
    |--------------------------------------------------------------------------
    |
    | PUT /api/cart/items/{item}
    | PATCH /api/cart/items/{item}
    |
    */

    public function update(
        UpdateCartItemRequest $request,
        string $item
    ): JsonResponse {
        try {
            $user = $this->getAuthenticatedUser(
                $request
            );

            $sessionId = null;

            /*
            |--------------------------------------------------------------------------
            | Guest Session
            |--------------------------------------------------------------------------
            */

            if (!$user) {
                $sessionId = $this->getGuestSessionId(
                    $request
                );

                if (!$sessionId) {
                    return ApiResponse::error(
                        'Guest cart session ID is required.',
                        400
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Item
            |--------------------------------------------------------------------------
            */

            $cart = $this->cartService->updateItem(
                itemUuid: $item,
                quantity: (int) $request->validated('quantity'),
                user: $user,
                sessionId: $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return $this->cartResponse(
                $cart,
                'Cart item updated successfully.',
                $sessionId
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Cart Item
    |--------------------------------------------------------------------------
    |
    | DELETE /api/cart/items/{item}
    |
    */

    public function destroy(
        Request $request,
        string $item
    ): JsonResponse {
        try {
            $user = $this->getAuthenticatedUser(
                $request
            );

            $sessionId = null;

            /*
            |--------------------------------------------------------------------------
            | Guest Session
            |--------------------------------------------------------------------------
            */

            if (!$user) {
                $sessionId = $this->getGuestSessionId(
                    $request
                );

                if (!$sessionId) {
                    return ApiResponse::error(
                        'Guest cart session ID is required.',
                        400
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Remove Item
            |--------------------------------------------------------------------------
            */

            $cart = $this->cartService->removeItem(
                itemUuid: $item,
                user: $user,
                sessionId: $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return $this->cartResponse(
                $cart,
                'Cart item removed successfully.',
                $sessionId
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Cart
    |--------------------------------------------------------------------------
    |
    | DELETE /api/cart
    |
    */

    public function clear(
        Request $request
    ): JsonResponse {
        try {
            $user = $this->getAuthenticatedUser(
                $request
            );

            $sessionId = null;

            /*
            |--------------------------------------------------------------------------
            | Guest Session
            |--------------------------------------------------------------------------
            */

            if (!$user) {
                $sessionId = $this->getGuestSessionId(
                    $request
                );

                if (!$sessionId) {
                    return ApiResponse::error(
                        'Guest cart session ID is required.',
                        400
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Clear Cart
            |--------------------------------------------------------------------------
            */

            $cart = $this->cartService->clearCart(
                user: $user,
                sessionId: $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return $this->cartResponse(
                $cart,
                'Cart cleared successfully.',
                $sessionId
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Authenticated User
    |--------------------------------------------------------------------------
    */

    protected function getAuthenticatedUser(
        Request $request
    ): ?\Illuminate\Contracts\Auth\Authenticatable {
        /*
        |--------------------------------------------------------------------------
        | Sanctum Authentication
        |--------------------------------------------------------------------------
        |
        | This works even though the Cart routes are not protected
        | by auth:sanctum middleware, allowing guest carts.
        |
        */

        return Auth::guard('sanctum')->user();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Existing Guest Session ID
    |--------------------------------------------------------------------------
    */

    protected function getGuestSessionId(
        Request $request
    ): ?string {
        $sessionId = $request->header(
            'X-Cart-Session'
        );

        if (!$sessionId) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Validate UUID
        |--------------------------------------------------------------------------
        */

        if (!Str::isUuid($sessionId)) {
            return null;
        }

        return $sessionId;
    }

    /*
    |--------------------------------------------------------------------------
    | Get/Create Guest Session ID
    |--------------------------------------------------------------------------
    */

    protected function getOrCreateGuestSessionId(
        Request $request
    ): string {
        $sessionId = $this->getGuestSessionId(
            $request
        );

        if ($sessionId) {
            return $sessionId;
        }

        return (string) Str::uuid();
    }

    /*
    |--------------------------------------------------------------------------
    | Cart Response
    |--------------------------------------------------------------------------
    */

    protected function cartResponse(
        $cart,
        string $message,
        ?string $sessionId = null,
        int $status = 200
    ): JsonResponse {
        $response = ApiResponse::success(
            new CartResource($cart),
            $message,
            $status
        );

        /*
        |--------------------------------------------------------------------------
        | Return Guest Session ID
        |--------------------------------------------------------------------------
        |
        | The frontend/Postman must save this value and send it
        | back using X-Cart-Session on future guest requests.
        |
        */

        if ($sessionId) {
            $response->header(
                'X-Cart-Session',
                $sessionId
            );
        }

        return $response;
    }

    /*
    |--------------------------------------------------------------------------
    | Exception Handler
    |--------------------------------------------------------------------------
    */

    protected function handleException(
        Throwable $e
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Business Logic Error
        |--------------------------------------------------------------------------
        */

        if ($e instanceof RuntimeException) {
            return ApiResponse::error(
                $e->getMessage(),
                400
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unexpected Error
        |--------------------------------------------------------------------------
        */

        report($e);

        return ApiResponse::error(
            'Something went wrong while processing the cart.',
            500
        );
    }
}

