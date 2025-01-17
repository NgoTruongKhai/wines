<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CheckoutController as ControllersCheckoutController;
use App\Models\orderdetails;
use Illuminate\Database\DeadlockException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Psy\TabCompletion\Matcher\FunctionsMatcher;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

date_default_timezone_set('Asia/Ho_Chi_Minh');

class CheckoutController extends Controller
{
    private $vnp_HashSecret = "OUNLJDFELTPRZUKCHFBFBBSMVNROUCGB"; //Secret key
    private $vnp_TmnCode = "HNM3NYHP"; //Website ID in VNPAY System

    public function vnpayPayment(Request $request)
    {
        $thanhpho = explode("_", $request->input('thanhpho'))[1];
        $quanhuyen = explode("_", $request->input('quan-huyen'))[1];
        $phuongxa = explode("_", $request->input('phuong-xa'))[1];

        session()->put('orders', [
            'fullname' => $request->input('pay-name'),
            'phonenumber' => $request->input('pay-phone'),
            'email' => $request->input('pay-email'),
            'address' =>  $request->input('pay-address') . ', ' . $phuongxa . ', ' . $quanhuyen . ', ' . $thanhpho,
            'total_price' => $request->input('pay-sum2'),
            // 'quantity' => $request->input('quantity')
        ]);

        if ($request->input('pay-options') == 'COD') {
            // return response()->json([$request->all(), session('cart')], 200);
            if (session()->has('tokenUser') && session()->has('UserID')) {
                $respon = Http::withToken(session('tokenUser'))->post(
                    'http://127.0.0.1:8001/api/v1/orders',
                    [
                        "customer_id" => session('UserID'),
                        "total" => session('orders')['total_price'],
                        "address" => session('orders')['address'],
                        "phone" => session('orders')['phonenumber'],
                        "fullname" => session('orders')['fullname'],
                        "email" => session('orders')['email'],
                    ]
                );
                $arrayIten = [];
                foreach (session('cart') as $item) {
                    Http::withToken(session('tokenUser'))->post(
                        'http://127.0.0.1:8001/api/v1/orderdetails',
                        [
                            array(
                                'productId' => $item['id'],
                                'price' => $item['price'],
                                'productName' => $item['name'],
                                'quantity' => $item['quantity'],
                                'orderId' => $respon['data'][0]['id'],
                            )
                        ]
                    );
                    array_push($arrayIten,  array(
                        'productId' => $item['id'],
                        'image' => $item['images'],
                        'price' => $item['price'],
                        'productName' => $item['name'],
                        'quantity' => $item['quantity'],
                        'orderId' => $respon['data'][0]['id'],
                    ));
                }

                $emailok = $request->input('pay-email'); //email cần gửi
                Mail::send(
                    "email.receipt",
                    [
                        'orderId' => $respon['data'][0]['id'],
                        'email' => $emailok,
                        'name' => $request->input('pay-name'),
                        'Items' => $arrayIten,
                        'Address' => $request->input('pay-address') . ', ' . $phuongxa . ', ' . $quanhuyen . ', ' . $thanhpho,
                        'Phone' => $request->input('pay-phone'),
                        'Total_price' => $request->input('pay-sum2')
                    ],
                    function ($email) use ($emailok) {
                        $email->subject('ĐẶT HÀNG THÀNH CÔNG');
                        $email->to($emailok, "xxxx");
                    }
                );
                session()->forget('cart');
                session()->forget('orders');
                return redirect('/cart?message=success');
            } else return 'Không có token và email user';
        }
        // session()->save();
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "http://127.0.0.1:8000/vnpay/vnpay_return";
        $vnp_TxnRef = random_int(1000000000, 9999999999);
        // $_POST['order_id']; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_OrderInfo = "Thanh toán hóa đơn";
        // $_POST['order_desc'];
        $vnp_OrderType = 'Mua hàng hóa';
        // $_POST['order_type'];
        $vnp_Amount = $request->input('pay-sum2') * 100;
        $vnp_Locale = 'vn';
        // $_POST['language'];
        $vnp_BankCode = 'NCB'; // chọn củ thể ngân hàng, không thì bỏ rỗng
        // $_POST['bank_code'];
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        //Add Params of 2.0.1 Version
        // $vnp_ExpireDate = $_POST['txtexpire'];
        //Billing
        // $vnp_Bill_Mobile = $_POST['txt_billing_mobile'];
        // $vnp_Bill_Email = $_POST['txt_billing_email'];
        // $fullName = trim($_POST['txt_billing_fullname']);
        // if (isset($fullName) && trim($fullName) != '') {
        //     $name = explode(' ', $fullName);
        //     $vnp_Bill_FirstName = array_shift($name);
        //     $vnp_Bill_LastName = array_pop($name);
        // }
        // $vnp_Bill_Address = $_POST['txt_inv_addr1'];
        // $vnp_Bill_City = $_POST['txt_bill_city'];
        // $vnp_Bill_Country = $_POST['txt_bill_country'];
        // $vnp_Bill_State = $_POST['txt_bill_state'];
        // // Invoice
        // $vnp_Inv_Phone = $_POST['txt_inv_mobile'];
        // $vnp_Inv_Email = $_POST['txt_inv_email'];
        // $vnp_Inv_Customer = $_POST['txt_inv_customer'];
        // $vnp_Inv_Address = $_POST['txt_inv_addr1'];
        // $vnp_Inv_Company = $_POST['txt_inv_company'];
        // $vnp_Inv_Taxcode = $_POST['txt_inv_taxcode'];
        // $vnp_Inv_Type = $_POST['cbo_inv_type'];
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );
        if (isset($vnp_BankCode) && $vnp_BankCode != "")
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        if (isset($vnp_Bill_State) && $vnp_Bill_State != "")
            $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($this->vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        if (isset($_POST['redirect'])) return redirect($vnp_Url);
        else return response()->json(['code' => '00', 'message' => 'success', 'data' => $vnp_Url]);
    }
    /*
    http://127.0.0.1:8000/vnpay/vnpay_return?
    vnp_Amount=1500000
    &vnp_BankCode=NCB
    &vnp_BankTranNo=VNP13865039
    &vnp_CardType=ATM
    &vnp_OrderInfo=Thanh+toan+hoa+don
    &vnp_PayDate=20221027153557
    &vnp_ResponseCode=00
    &vnp_TmnCode=HNM3NYHP
    &vnp_TransactionNo=13865039
    &vnp_TransactionStatus=00
    &vnp_TxnRef=-1695467182009401481
    &vnp_SecureHash=403642a307471e8c789c9a6d0d22640e22569aa7ef7053de20373181278b6e931068e94a0cecd2cef108a18ec0455826a551b23deecc0ee63f9d33496c2c4cb1
     */

    public function paymentsResult(Request $request)
    {
        // return session('cart');
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == 'vnp_') {
                $inputData[$key] = $value;
            }
        }
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = '';
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
        }
        $secureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);
        // @dd($secureHash . '---' . $vnp_SecureHash);
        if ($secureHash == $vnp_SecureHash) {
            // lưu vô bảng đơn hàng và chi tiết đơn hàng
            if ($_GET['vnp_ResponseCode'] == '00') {
                $getId = Http::withToken(session('tokenUser'))->get('http://127.0.0.1:8001/api/v1/customers/' . session('UserID'));
                if (session()->has('tokenUser') && session()->has('UserID')) {
                    $respon = Http::withToken(session('tokenUser'))->post(
                        'http://127.0.0.1:8001/api/v1/orders',
                        [
                            "customer_id" => session('UserID'),
                            "total" => session('orders')['total_price'],
                            "address" => session('orders')['address'],
                            "phone" => session('orders')['phonenumber'],
                            "fullname" => session('orders')['fullname'],
                            "email" => session('orders')['email'],
                        ]
                    );
                } else return 'Không có token và email admin';
                // return response($respon, 200);
                // Ngân hàng: NCB
                // Số thẻ: 9704198526191432198
                // Tên chủ thẻ: NGUYEN VAN A
                // Ngày phát hành: 07/15
                // Mật khẩu OTP: 123456
                $arrayIten = [];
                if (session()->has('tokenUser') && session()->has('UserID')) {
                    foreach (session('cart') as $item) {
                        // return $item;
                        $respon2 = Http::withToken(session('tokenUser'))->post(
                            'http://127.0.0.1:8001/api/v1/orderdetails',
                            [
                                array(
                                    'productId' => $item['id'],
                                    'price' => $item['price'],
                                    'productName' => $item['name'],
                                    'quantity' => $item['quantity'],
                                    'orderId' => $respon['data'][0]['id'],
                                )
                            ]
                        );
                        array_push($arrayIten,  array(
                            'productId' => $item['id'],
                            'price' => $item['price'],
                            'productName' => $item['name'],
                            'quantity' => $item['quantity'],
                            'orderId' => $respon['data'][0]['id'],
                        ));
                    }
                } else return 'Không có token và email admin';

                $emailok = session('orders')['email']; //email cần gửi
                Mail::send(
                    "email.receipt",
                    [
                        'orderId' => $respon['data'][0]['id'],
                        'email' => session('orders')['email'],
                        'name' => session('orders')['fullname'],
                        'Items' => $arrayIten,
                        'Address' => session('orders')['address'],
                        'Phone' => session('orders')['phonenumber'],
                        'Total_price' => session('orders')['total_price'],
                    ],
                    function ($email) use ($emailok) {
                        $email->subject('ĐẶT HÀNG THÀNH CÔNG');
                        $email->to($emailok, "xxxx");
                    }
                );
                session()->forget('cart');
                session()->forget('orders');
                $Result = 'Giao dịch thành công';
                // }
            } else {
                $Result = 'Giao dịch không thành công';
            }
        } else {
            $Result = 'Chu kỳ không hợp lệ';
        }
        // return response($respon2, 200);
        return view("vnpay.vnpay_return", [
            'vnp_TxnRef' => $request->input('vnp_TxnRef'),
            'vnp_OrderInfo' => $request->input('vnp_OrderInfo'),
            'vnp_ResponseCode' => $request->input('vnp_ResponseCode'),
            'vnp_TransactionNo' => $request->input('vnp_TransactionNo'),
            'vnp_BankCode' => $request->input('vnp_BankCode'),
            'vnp_PayDate' => $request->input('vnp_PayDate'),
            'vnp_Amount' => $request->input('vnp_Amount'),
            'fullname' => $getId['data']['lastname'] . ' ' . $getId['data']['firstname'],
            'Result' => $Result
        ]);
    }
}
