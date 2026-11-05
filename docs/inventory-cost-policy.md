# Inventory cost policy — phase one

- Quantity is stored to three decimal places. Inventory value is stored to six, and the derived moving average to ten. Monetary input is parsed as decimal text with `brick/math`; no binary floating point is used in the inventory ledger.
- A purchase receipt, an opening balance, or a documented positive adjustment adds `quantity × stated unit cost` to inventory value. The new average is `new inventory value ÷ new quantity`. Ordinary issues do not reweight the average.
- A sale, supplier return, negative adjustment, damage, or repair part usage removes inventory at the current carrying cost. The final issue removes the entire remaining value, including any fractional rounding residual.
- A customer return or sale cancellation adds the returned units at the original sale item's cost snapshot. This can change the current average because an older cost basis has reentered the pool; it never changes the historical sale snapshot.
- A supplier return's payable credit remains based on the original purchase line. The difference between that credit and inventory value removed is recorded on the return item as `cost_variance`; posting that difference to a general ledger is outside this phase.
- Historical positive balances without an auditable cost stay `inventory_value = NULL`. The valuation service returns `NULL` as the total and reports the number of unknown rows. Such balances must be reconciled before issuing stock. A zero balance has zero value and takes the next receipt's cost.
- `stock_movements.value_delta`, `value_before`, and `value_after` form the cost audit trail. `event_key` is unique when supplied. Purchase receipt, sale, cancellation, and return services supply stable keys; the return operations accept an optional request idempotency key.
- The new `variant_key` uses zero for no variant, allowing a unique constraint to protect that stock row on databases where `NULL` entries in a unique index are distinct.
