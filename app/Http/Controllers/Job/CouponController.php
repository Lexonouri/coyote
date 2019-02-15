<?php

namespace Coyote\Http\Controllers\Job;

use Coyote\Http\Controllers\Controller;
use Coyote\Repositories\Contracts\CouponRepositoryInterface;

class CouponController extends Controller
{
    /**
     * @var CouponRepositoryInterface
     */
    private $coupon;

    /**
     * @param CouponRepositoryInterface $coupon
     */
    public function __construct(CouponRepositoryInterface $coupon)
    {
        parent::__construct();

        $this->coupon = $coupon;
    }

    /**
     * @return array|mixed
     */
    public function validateCode()
    {
        $result = $this->coupon->findBy('code', $this->request->input('code'));

        if (!$result) {
            return [];
        }

        return $result;
    }
}
