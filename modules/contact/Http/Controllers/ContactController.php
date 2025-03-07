<?php

namespace Diji\Contact\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Contact\Http\Requests\StoreContactRequest;
use Diji\Contact\Http\Requests\UpdateContactRequest;
use Diji\Contact\Resources\ContactResource;
use Diji\Contact\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query();

        if($request->filled('email')){
            $query->where("email", $request->email);
        }

        return ContactResource::collection($query->get())->response();
    }

    public function show(int $contact_id): \Illuminate\Http\JsonResponse
    {
        $contact = Contact::find($contact_id);

        return response()->json([
            'data' => new ContactResource($contact)
        ]);
    }

    public function store(StoreContactRequest $request)
    {
        $data = $request->validated();

        $contact = Contact::create($data);

        return response()->json([
            'data' => new ContactResource($contact),
        ], 201);
    }

    public function update(UpdateContactRequest $request, int $contact_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $contact = Contact::find($contact_id);

        $contact->update($data);

        return response()->json([
            'data' => new ContactResource($contact),
        ]);
    }


    public function destroy(Contact $contact): \Illuminate\Http\Response
    {
        $contact->delete();

        return response()->noContent();
    }
}
