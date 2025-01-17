<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Http\Resources\V1\OrderCollection;
use App\Filters\V1\OrderFilter;
use App\Http\Requests\BulkStoreOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

date_default_timezone_set('Asia/Ho_Chi_Minh');

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * get orderdetails by customer id
     * http://localhost:8000/api/v1/orders?customerId[eq]=1
     * 
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        /**
         * check auth
         */
        // $user = request()->user();
        // if ($user->role_as == 0) {
        //     $filler = new OrderFilter();
        //     $fillerItems = $filler->transform($request);
        //     $order = Order::where($fillerItems)->where('customer_id', $user->id);
        //     return $order->paginate();
        // }

        $filler = new OrderFilter();
        $fillerItems = $filler->transform($request);
        $order = Order::where($fillerItems);
        return $order->paginate();
    }

    // hamf thoong ke
    public function statistic(Request $request)
    {
        $data = array();
        $dataReceipts = array();
        $dataRhino = array();
        $ahihi = array('receipts' => [], 'rhino' => []);
        if ($request->query('year')) {
            for ($i = 1; $i <= 12; $i++) {
                $order = Order::whereYear('created_at', $request->year)->where('status', 1)->whereMonth('created_at', $i);
                array_push($dataReceipts, $order->count(),);
                array_push($dataRhino, $order->sum('total'));
            }
        }
        $ahihi['receipts'] = $dataReceipts;
        $ahihi['rhino'] = $dataRhino;
        return response()->json($ahihi, 200);
        // return $data;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOrderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderRequest $request)
    {
        $order = Order::create($request->all());
        return response()->json([
            'status' => false,
            'message' => 'Không tìm thấy hóa đơn',
            'data' => [$order]
        ], 200);
    }

    public function bulkStore(BulkStoreOrderRequest  $request)
    {
        $bulk = collect($request->all())->map(function ($arr, $key) {
            return Arr::except($arr, ['customerId', 'billedDate', 'paidDate']);
        });

        Order::insert($bulk->toArray());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // $user = request()->user();
        // if ($user->role_as == 0) {
        //     $orderUser = Order::where('id', $order->id)->with('orderDetails')->get();
        //     return new OrderCollection($orderUser);
        // }
        if ($order = Order::find($id)) {
            return new OrderResource($order);
        }
        return response()->json([
            'status' => false,
            'message' => 'Không tìm thấy hóa đơn',
        ], 404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOrderRequest  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order->update($request->all());
        return response()->json([
            'status' => true,
            'message' => 'Updated status successfully !',
            'data' => [
                $order
            ]
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
