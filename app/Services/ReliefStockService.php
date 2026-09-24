<?php

namespace App\Services;

use App\Enums\InOutType;
use App\Models\Barangay;
use App\Models\Inventory;
use App\Models\PackReceipt;
use App\Models\Sale;
use Error;
use ErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\error;

// TODO(later) - error handling, testing
class ReliefStockService
{

    // this needs to do create for sale,payroll, and add_ingredeint✅


    

    // $data=[{
//     reciept=>$reciept,
// ],
//     reliefList =>[$reliefList],
// }]
//this is not like mb where we can add directly to brach, it needs to go only the lgu, or nothing in this case, i could have done this in mb too but fuck it men, its good actually becuase we want some limitation 
    function reliefStockStore(array $data)
    {

        try {

            return DB::transaction(
                function () use ($data) {


                    $rp = new PackReceipt();
                    $rp->fill($data['receipt']);
                    $rp->received_by = auth()->id();
                    $rp->save();


                    foreach ($data['reliefList'] as $listItem) {
                        $productID = $listItem['relief_pack_id'];
                        $qty = $listItem['quantity'];

                        // we are just adding no need to lock or check stock
                        $rp->reliefStock()->create([
                            'relief_pack_id' => $productID,
                            'quantity' => $qty,
                        ]);


                    }
        // dd($rp,$data);
    
                    return $rp; 
    
               
                }
                
            );

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            //   ask what is this for
            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
            ) {
                Log::error($th);
            }
        }



    }



    // we need ot figure out what form of data will this recieve

    // ok we will revice data in json form, or object, since it is better for scale an its
    // is more maintable since we have single source, but honestly thier is likely no choice
    // it grow to the point it will be a problem if we put it in different var but i like that 
    // object array we get to laern more, of course if i mess up i will just go back to what i know
// $data=[{
//     inventory=>[type=>IN,
//     encoder_id=>3,
//     inventory_type=>,
// ],
//     productList =>[product1QTY=>1,product2QTY=>1,,product3QTY=>1,],
//     sale=>[barangay_id=>1,inventory_id=>id,shift=>openning,cash_amount=>50,gcash_amount=>50]
// }]
    function InventoryOUT(array $data)
    {
        // $data=['barangay_id'=>1,'ingredient_id'=>1,'quantity'=>10]

        try {

            return DB::transaction(
                function () use ($data) {

                    //FAULT-TOLERANCE sicne this is here we might as will use it to check, if i fail whole thing should stop
                    //ASK-YOURSELF we will have validation for barangay:exist and that is enough so we dont really need the find or fila
                    //we can use just fine, but it is  not bad to use findorfail but if it has performance added on top then we weill use find()
                    $barangay = barangay::findOrFail($data['barangay_id']);
                    $inv = new Inventory();
                    $inv->fill($data['inventory']);

                    $inv->encoder_id = auth()->id();
                    $inv->inventory_type = InOutType::Out->value;
                    $inv->barangay_id = $barangay->id; //ensure only insert if it exist, instead fo data[barangay_id] which has possiblity of not existing
                    $inv->save();

                    $sale = new Sale();
                    $sale->fill($data['sale']);

                    //ASK-YOURSELF i dont know if this will work since we are doing it in same transaction
                    $sale->barangay_id = $barangay->id; //ensure only insert if it exist, instead fo data[barangay_id] which has possiblity of not existing, also becuase input is not validated;
                    $sale->inventory_id = $inv->id;
                    $sale->save();


                    foreach ($data['productList'] as $listItem) {
                        $productID = $listItem['product_id'];
                        $qty = $listItem['quantity'];
                        $inv->items()->create([
                            'product_id' => $productID,
                            'quantity' => $qty,
                        ]);
                        // FAULT-TOLERANCE - this just for safe check, can be remove, ask 
                        // ASK-YOURSELF later, because i suspect we dont need this , because we expect product to be already thier
                        //OPTIMIZE - seem redundant to get the whole model 
                        $existting = $barangay->products()->where('product_id', $productID)->first();
                        if ($existting) {
                            $barangay->products()->updateExistingPivot( //update to follow pattern 2
                                $productID,
                                [
                                    'quantity' => $existting->pivot->quantity - $qty,
                                    //with need ot add withpivot for model 
                                ],
                            );

                        } else {
                            // dd($existting, 'bad');

                            //we need to throw error here since , in here we expect that an product already exist in this barangay
                            //if not then something is wrong, we should'nt even be able to make this request
                            throw new Error('development: this error is bad, INVENTORYOUT, unexpected error');
                        }

                    }

                    return $inv; //no reason to do this other than confirm operation ws successfull
    

                }
            );

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            //   ask what is this for
            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
            ) {
                Log::error($th);
            }
        }



    }

    function inventoryUpdateIn(Inventory $inventory, array $data)
    {
        try {
            return DB::transaction(function () use ($inventory, $data) {
                $barangay = barangay::findOrFail($data['barangay_id']);


                $inventory->fill($data['inventory']);
                $inventory->barangay_id = $barangay->id;
                $inventory->save(); //this is update 

                // reverse old quantities first
                foreach ($inventory->items as $oldItem) {
                    $pivot = $barangay->products()->where('product_id', $oldItem->product_id)->first();
                    if ($pivot) {
                        // // OPTIMIZE - instead of doing it like this where we loop to get the old product()->item()
                        // quantity and minusing that to $barangay->products() current quantity to reverse the prev opertation,
                        //  why not just take the  old product()->item()  quantity then minus that to incoming quantity from productList
                        //  that way we get the difference and that will be the one to be put up against the barangay->product()
                        //  this reduce number of operation, like instead of doing  4 operation at once in one foreach, 
                        //  we can just do 1 operation

                        // TODO(later) - this needs check becuase what if old->quntity(15) is
                        //  greater than  $pivot->pivot->quantity(10) then that will make current stock negative
                        //  which is not allowed, its fine since its in transaction but think this though

                        $barangay->products()->updateExistingPivot($oldItem->product_id, [
                            'quantity' => $pivot->pivot->quantity - $oldItem->quantity,
                        ]);
                    }
                }

                // wipe old line items, apply new ones
                $inventory->items()->delete();

                foreach ($data['productList'] as $listItem) {
                    $productID = $listItem['product_id'];
                    $qty = $listItem['quantity'];

                    $existing = $barangay->products()->where('product_id', $productID)->first();
                    if ($existing) {
                        $barangay->products()->updateExistingPivot($productID, [
                            'quantity' => $existing->pivot->quantity + $qty,
                        ]);
                    } else {
                        $barangay->products()->attach($productID, ['quantity' => $qty, 'barangay_id' => $barangay->id]);
                    }

                    $inventory->items()->create(['product_id' => $productID, 'quantity' => $qty]);
                }

                return $inventory;
            });
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error($th);
            return null;
        }
    }



    function inventoryUpdateOut(Inventory $inventory, array $data)
    {
        try {
            return DB::transaction(function () use ($inventory, $data) {
                $barangay = barangay::findOrFail($data['barangay_id']);

                $inventory->fill($data['inventory']);
                $inventory->barangay_id = $barangay->id;
                $inventory->save();

                $inventory->sale->fill($data['sale']);
                $inventory->sale->barangay_id = $barangay->id;
                $inventory->sale->save();

                // reverse old quantities (adding stock back that was minus before)
                foreach ($inventory->items as $oldItem) {
                    $pivot = $barangay->products()->where('product_id', $oldItem->product_id)->first();
                    if ($pivot) {
                        $barangay->products()->updateExistingPivot($oldItem->product_id, [
                            'quantity' => $pivot->pivot->quantity + $oldItem->quantity,
                        ]);
                    }
                }

                $inventory->items()->delete();

                // apply new quantities (subtract stock)
                foreach ($data['productList'] as $listItem) {
                    $productID = $listItem['product_id'];
                    $qty = $listItem['quantity'];

                    $existing = $barangay->products()->where('product_id', $productID)->first();
                    if (!$existing) {
                        throw new Error('development: this error is bad, INVENTORYUPDATEOUT, unexpected error');
                    }

                    $barangay->products()->updateExistingPivot($productID, [
                        'quantity' => $existing->pivot->quantity - $qty,
                    ]);

                    $inventory->items()->create(['product_id' => $productID, 'quantity' => $qty]);
                }

                return $inventory;
            });
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error($th);
            return null;
        }
    }

    function inventoryDelete(Inventory $inventory): bool
{
    try {
        return DB::transaction(function () use ($inventory) {
            $barangay = barangay::findOrFail($inventory->barangay_id);

            foreach ($inventory->items as $item) {
                $pivot = $barangay->products()->where('product_id', $item->product_id)->first();

                if ($pivot) {
                    // IN added stock, so deleting it must subtract back out.
                    // OUT subtracted stock, so deleting it must add back in.
                    $sign = $inventory->inventory_type === InOutType::In->value ? -1 : 1;

                    $barangay->products()->updateExistingPivot($item->product_id, [
                        'quantity' => $pivot->pivot->quantity + ($sign * $item->quantity),
                    ]);
                }
                // NOTE - if $pivot is missing entirely here, that means the product
                // was removed from the barangay some other way after this inventory
                // record was created. Silently skipping is the safe default (nothing
                // to reverse against), but you may want to Log::warning this case —
                // it usually signals a data integrity issue worth knowing about.
            }

            // items cascade-delete via the FK (cascadeOnDelete() from earlier migration)
            // sale cascade-deletes too if you set that FK up the same way
            $inventory->delete();

            return true;
        });
    } catch (\Throwable $th) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Log::error($th);
        return false;
    }
}
}


// finish this today so that we can move on to to pern which should be easier, learn isolation for it   

// figure out how you will send data here first and fins the fucing pivot pk    