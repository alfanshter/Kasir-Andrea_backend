<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Keranjang;
use App\Models\Pesanan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PesananController extends Controller
{
    public function tambah_transaksi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_user' => 'required',
            'nama' => ['required'],
            'telepon' => ['required'],
            'alamat' => ['required'],
            'kurir' => ['required'],
            'harga_total' => ['required'],
            'harga' => ['required'],
            'ongkir' => ['required']
        ]);


        if ($validator->fails()) {
            $response = [
                'message' => 'kesalahan',
                'status' => 2,
                'validator' => $validator->errors()
            ];
            return response()->json($response, 200);
        }
        $data = $request->all();
        date_default_timezone_set('Asia/Jakarta');
        $hari = date('m-Y h:i:s', time());
        // Given string

        $nomorpesanan = $this->RemoveSpecialChar($hari) . $this->RemoveSpecialChar(strtolower($request->nama)) . $request->id_user;
        $data['nomorpesanan'] = $nomorpesanan;
        //tambah pesanan
        $pesanan = Pesanan::create($data);
        //update keranjang
        Keranjang::where('id_user', $request->id_user)
            ->where('is_status', 0)
            ->update([
                'is_status' => 1,
                'nomorpesanan' => $nomorpesanan
            ]);

        $response = [
            'message' => 'berhasil insert',
            'status' => 1,
            'data' => $pesanan
        ];

        return response()->json($response, 200);
    }

    public function pesanan_selesai(Request $request)
    {
        $update = Pesanan::where('id', $request->id)->update([
            'is_status' => 1
        ]);
        $response = [
            'message' => 'berhasil update',
            'status' => 1
        ];

        return response()->json($response, 200);
    }

    public function get_pesanan_id(Request $request)
    {
        $data = Pesanan::where('id_user', $request->input('id_user'))
            ->get();

        $response = [
            'message' => 'berhasil diambil',
            'status' => 1,
            'data' => $data
        ];

        return response()->json($response, 200);
    }

    public function get_pesanan_owner(Request $request)
    {
        $data = Pesanan::orderBy('created_at', 'desc')
            ->get();


        $response = [
            'message' => 'berhasil diambil',
            'status' => 1,
            'data' => $data
        ];

        return response()->json($response, 200);
    }


    public function cetak_nota(Request $request)
    {
        $data = Pesanan::where('id', $request->id)
            ->first();

        $keranjang = Keranjang::where('nomorpesanan', $request->nomorpesanan)
            ->with('produk')
            ->get();

        $pdf = Pdf::loadView(
            'nota.nota',
            [
                'nota' => $data,
                'keranjang' => $keranjang
            ]
        )->setPaper('a4', 'potrait');

        $nomorpesanan = $request->nomorpesanan;

        $content = $pdf->download()->getOriginalContent();
        Storage::put("public/csv/nota/nota-$nomorpesanan.pdf", $content);
        $response = [
            'message' => 'sudah seawater hari ini',
            'status' => 1,
            'data' => url('/') . "/storage/csv/nota/nota-$nomorpesanan.pdf"
        ];
        return response()->json($response, 200);
    }

    function RemoveSpecialChar($str)
    {

        // Using str_replace() function 
        // to replace the word 
        $res = str_replace(array(
            '\'', '"',
            ',', ';', '<', '>', '-', ' ', ':'
        ), '', $str);

        // Returning the result 
        return $res;
    }
}
