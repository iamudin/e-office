<?php

namespace App\Http\Controllers\Plugins\EOffice\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Plugins\EOffice\Pejabat;
use App\Models\Plugins\EOffice\User;

class PejabatController extends Controller
{
    public function update(Request $request,Pejabat $pejabat)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        
        $messages = [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.unique' => 'Nama sudah digunakan oleh pejabat lain.',
            'nip.regex' => 'NIP hanya boleh berisi angka dan spasi.',
            'nip.unique' => 'NIP sudah digunakan oleh pejabat lain.',
            'nohp.required' => 'Nomor HP (WhatsApp) wajib diisi.',
            'nohp.numeric' => 'Nomor HP harus berupa angka.',
            'nohp.regex' => 'Nomor HP harus diawali dengan 08 atau 628.',
            'nohp.unique' => 'Nomor HP sudah digunakan oleh pejabat lain.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'username.required' => 'Username login wajib diisi.',
            'username.min' => 'Username minimal 4 karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan garis bawah (_).',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.regex' => 'Password tidak boleh mengandung spasi.',
            'jabatan.required' => 'Nama jabatan tertulis wajib diisi.',
        ];

        $request->validate([
            'nip'=>['nullable','regex:/^[0-9 ]+$/','unique:pejabats,nip,'.$pejabat->id],
            'nohp'=>['required', 'numeric', 'regex:/^(08|628)[0-9]*$/', 'unique:pejabats,nohp,'.$pejabat->id],
            'nama'=>'required|unique:pejabats,nama,'.$pejabat->id,
            'email'=>'required|email|unique:users,email,'.$pejabat->user->id,
            'username'=>['required', 'min:4', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username,'.$pejabat->user->id],
            'password'=>['nullable', 'min:6', 'regex:/^\S+$/'],
            'jabatan'=>'required',
        ], $messages);

        $pejabat->update(array_merge($request->all(),['penerima_disposisi'=>$request->penerima_disposisi ?? 0]));
        $pejabat->user()->update([
            'username'=>$request->username,
            'password'=>$request->password ? bcrypt($request->password) : $pejabat->user->password,
            'name'=> $request->nama,
            'email'=> $request->email,
            'status'=> $request->status,
            'slug'=> str($request->nama)->slug(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil diperbarui']);
        }

        return back()->with('success','Berhasil di perbarui');
    }
    public function edit(Pejabat $pejabat)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        
        $viewData = [
            'data' => $pejabat->load('user'),
            'penerima_disposisi' => Pejabat::whereNotIn('id',[$pejabat->id])->get(),
        ];

        if (request()->ajax()) {
            $viewData['is_modal'] = true;
            return view('e-office::admin.pejabat._form', $viewData);
        }

        return view('e-office::admin.pejabat.form', $viewData);
    }
    public function store(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');

        $messages = [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.unique' => 'Nama sudah digunakan oleh pejabat lain.',
            'nip.regex' => 'NIP hanya boleh berisi angka dan spasi.',
            'nip.unique' => 'NIP sudah digunakan oleh pejabat lain.',
            'nohp.required' => 'Nomor HP (WhatsApp) wajib diisi.',
            'nohp.numeric' => 'Nomor HP harus berupa angka.',
            'nohp.regex' => 'Nomor HP harus diawali dengan 08 atau 628.',
            'nohp.unique' => 'Nomor HP sudah digunakan oleh pejabat lain.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'username.required' => 'Username login wajib diisi.',
            'username.min' => 'Username minimal 4 karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan garis bawah (_).',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.regex' => 'Password tidak boleh mengandung spasi.',
            'jabatan.required' => 'Nama jabatan tertulis wajib diisi.',
        ];

        $request->validate([
            'nip'=>['nullable','regex:/^[0-9 ]+$/','unique:pejabats,nip'],
            'nohp'=>['required', 'numeric', 'regex:/^(08|628)[0-9]*$/', 'unique:pejabats,nohp'],
            'nama'=>'required|unique:pejabats,nama',
            'email'=>'required|email|unique:users,email',
            'username'=>['required', 'min:4', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'password'=>['required', 'min:6', 'regex:/^\S+$/'],
            'jabatan'=>'required',
        ], $messages);

        $user = User::create([
            'username'=>$request->username,
            'password'=>bcrypt($request->password),
            'name'=> $request->nama,
            'email'=> $request->email,
            'status'=> 'active',
            'level'=> 'e-office',
            'slug'=> str($request->nama)->slug(),
        ]);
        $create = $user->pejabat()->create(array_merge($request->all(),['penerima_disposisi'=>$request->penerima_disposisi ?? 0]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil ditambah', 'id' => $create->id]);
        }

        return redirect(route('e-office.pejabat.edit',$create->id))->with('success','Berhasil ditambah');
    }
    public function create()
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        return view('e-office::admin.pejabat.form', 
        [
            'data' => null,
            'penerima_disposisi' => Pejabat::wherePenerimaDisposisi(1)->get(),
            ]
    );
    }
    public function index()
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        $data = [];
        return view('e-office::admin.pejabat.index', $data);
    }
    public function datatable(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        $data = Pejabat::withWhereHas('user');
        return DataTables::of($data)
            ->addIndexColumn()
            ->filterColumn('nama', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('pejabats.nama', 'like', "%{$keyword}%")
                      ->orWhereHas('user', function ($uq) use ($keyword) {
                          $uq->where('name', 'like', "%{$keyword}%")
                            ->orWhere('username', 'like', "%{$keyword}%");
                      });
                });
            })
            ->filterColumn('jabatan', function ($query, $keyword) {
                $query->where('pejabats.jabatan', 'like', "%{$keyword}%");
            })
            ->filterColumn('alias_jabatan', function ($query, $keyword) {
                $query->where('pejabats.alias_jabatan', 'like', "%{$keyword}%");
            })
            ->filterColumn('alias', function ($query, $keyword) {
                $query->where('pejabats.alias_jabatan', 'like', "%{$keyword}%");
            })
            ->filterColumn('nohp', function ($query, $keyword) {
                $query->where('pejabats.nohp', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="flex items-center gap-1">';
                $btn .= '<a href="'.route('e-office.pejabat.edit',$row->id).'" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors"><i class="fa fa-edit"></i></a>';
                $btn .= '</div>';
                return $btn;
            })
            ->addColumn('alias', function ($row) {
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">'.$row->alias_jabatan.'</span>';
            })
            ->addColumn('status', function ($row) {
                $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '.($row->user->isActive() ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700').'">'.str($row->user->status)->upper().'</span><br>';
                $status .= '<span class="text-xs text-gray-500">Login: '.$row->user->last_login_at?->diffForhumans().'</span><br>';
                $status .= '<span class="text-xs text-gray-400">IP: '.$row->user->last_login_ip.'</span>';
                return $status;
            })

            ->rawColumns(['nama', 'action','status','alias'])

            ->toJson();
    }

    public function struktur()
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        
        // Ambil struktur hierarki mulai dari yang tidak punya atasan
        $pejabats = Pejabat::with('staff.staff')->whereNull('atasan_id')->get();
        // Ambil semua pejabat untuk pilihan select
        $semuaPejabat = Pejabat::orderBy('nama')->get();

        return view('e-office::admin.pejabat.struktur', compact('pejabats', 'semuaPejabat'));
    }

    public function updateStruktur(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        
        $request->validate([
            'pejabat_id' => 'required',
            'atasan_id' => 'nullable'
        ]);

        if ($request->pejabat_id === $request->atasan_id) {
            return back()->with('error', 'Pejabat tidak boleh menjadi atasan bagi dirinya sendiri.');
        }

        $pejabat = Pejabat::findOrFail($request->pejabat_id);
        $pejabat->update(['atasan_id' => $request->atasan_id]);

        return back()->with('success', 'Struktur bawahan berhasil diperbarui.');
    }

    public function updateStrukturAjax(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        
        $data = json_decode($request->input('data'), true);
        if ($data) {
            // First, set all atasan_id to null to reset hierarchy
            Pejabat::query()->update(['atasan_id' => null]);
            $this->processHierarchy($data, null);
        }

        return response()->json(['success' => true]);
    }

    private function processHierarchy($items, $parentId)
    {
        foreach ($items as $item) {
            $pejabat = Pejabat::find($item['id']);
            if ($pejabat) {
                $pejabat->update(['atasan_id' => $parentId]);
            }
            if (!empty($item['children'])) {
                $this->processHierarchy($item['children'], $item['id']);
            }
        }
    }

    public function getTupoksi($pejabatId)
    {
        $pejabat = Pejabat::findOrFail($pejabatId);
        return response()->json($pejabat->tupoksis()->orderBy('created_at', 'asc')->get());
    }

    public function storeTupoksi(Request $request, $pejabatId)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        
        $request->validate([
            'tugas_fungsi' => 'required|string'
        ]);

        $pejabat = Pejabat::findOrFail($pejabatId);
        $tupoksi = $pejabat->tupoksis()->create([
            'tugas_fungsi' => $request->tugas_fungsi
        ]);

        return response()->json(['success' => true, 'data' => $tupoksi]);
    }

    public function deleteTupoksi($id)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');
        
        $tupoksi = \App\Models\Plugins\EOffice\PejabatTupoksi::findOrFail($id);
        $tupoksi->delete();

        return response()->json(['success' => true]);
    }
}
