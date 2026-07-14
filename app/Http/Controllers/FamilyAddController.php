<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyAddController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        $primaryMember = $user->ensureLinkedFamilyMember();

        return view('family.add', [
            'primaryMember' => $primaryMember,
        ]);
    }
}
