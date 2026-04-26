<?php
// discount_helper.php — Automatic 20% discount logic for Students, PWDs, Senior Citizens

/**
 * Check if the current user qualifies for the mandatory 20% discount.
 * The discount is applied automatically based on the user's verified classifications.
 * No toggle is needed — it's always active.
 *
 * @return bool True if the user qualifies for the 20% discount
 */
function userQualifiesForDiscount() {
    $status = $_SESSION['status'] ?? 'pending';
    $classifications = $_SESSION['classifications'] ?? [];

    // Only verified users get the discount
    if ($status !== 'verified') {
        return false;
    }

    $discountClasses = ['Student', 'PWD', 'Senior Citizen'];
    foreach ($classifications as $cls) {
        if (in_array($cls, $discountClasses)) {
            return true;
        }
    }

    return false;
}

/**
 * Apply the 20% discount to a fare amount if the user qualifies.
 *
 * @param float $fare The original fare amount
 * @return array ['original' => float, 'discounted' => float, 'hasDiscount' => bool, 'discountType' => string]
 */
function applyAutoDiscount($fare) {
    $hasDiscount = userQualifiesForDiscount();
    $classifications = $_SESSION['classifications'] ?? [];

    $discountType = '';
    if ($hasDiscount) {
        $discountClasses = ['Student', 'PWD', 'Senior Citizen'];
        $matched = array_intersect($classifications, $discountClasses);
        $discountType = implode(', ', $matched);
    }

    return [
        'original' => round($fare, 2),
        'discounted' => $hasDiscount ? round($fare * 0.80, 2) : round($fare, 2),
        'hasDiscount' => $hasDiscount,
        'savings' => $hasDiscount ? round($fare * 0.20, 2) : 0,
        'discountType' => $discountType
    ];
}
?>
