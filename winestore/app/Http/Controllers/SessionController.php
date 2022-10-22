<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class SessionController extends Controller
{

    public function store($id)
    {
        $item = Product::find($id);
        $cart = session('cart');
        $cart[$id] =
            [
                'id' => $id,
                'name' => $item->name,
                'image' => $item->image,
                'quantity' => 1,
                'price' => $item->price,
            ];
        session()->put('cart', $cart);
        return response($item, 200);
    }

    public function add($id)
    {
        $cart = session('cart');
        $item = Product::find($id);
        $arr = array('arr1' => '', 'arr2' => 0, 'arr3' => 0);
        $sum = 0;
        if (isset($cart[$id])) {
            if ($cart[$id]['quantity'] < $item->quantity) {
                $cart[$id]['quantity'] = $cart[$id]['quantity'] + 1;
            } else return response('Vượt số lượng kho !', 200);
        }

        foreach (session('cart') as $key => $value) {
            $sum += $value['price'] * $value['quantity'];
            $arr['arr1'] .= 
    '<tr>
        <td>
            <div class="d-flex flex-column align-items-center">
                <img class="img"
                    src="https://vinoteka.vn/assets/components/phpthumbof/cache/071801-1.3899b5ec6313090055de59b4621df17a.jpg"
                    width="82"><span class="">' . $value['name'] . '</span>
            </div>
        </td>
        <td>
            <div class="d-flex">
                <button class="btn bi bi-dash-circle"
                    onclick="minustocart(' . $value['id'] . ')"></button>
                <input type="text" min="1" max="99" step="1" disabled
                    class="btn" value="' . $value['quantity'] . '"
                    style="text-align: center; width: 3rem;">
                <button class="btn bi bi-plus-circle"
                    onclick="addtocart(' . $value['id'] . ')"></button>
            </div>
        </td>
        <td>' . number_format($value['price']) . '</td>
        <td>
            <div class="row">
                <span class="col-12">' . number_format($value['price'] * $value['quantity']) . '</span>
                <span class="col-12 text-decoration-underline text-danger"
                    onclick="deletedItem(' . $value['id'] . ')">Xóa</span>
            </div>
        </td>
    </tr>';
        }
        session()->put('cart', $cart);
        return response($cart[$id], 200);
    }

    public function minus($id)
    {
        $cart = session('cart');
        if (isset($cart[$id])) {
            if ($cart[$id]['quantity'] > 1) {
                $cart[$id]['quantity'] = $cart[$id]['quantity'] - 1;
                session()->put('cart', $cart);
            } else {
                session()->pull('cart.' . $id);
            }
        }
        // $respon=Http::get('https://provinces.open-api.vn/api/p');
        // $apiOk=$respon->json();
        return response('success', 200);
    }

    public function delete($id)
    {
        $cart = session('cart');
        if (isset($cart[$id])) {
            session()->pull('cart.' . $id);
        }
        return response('success', 200);
    }
}