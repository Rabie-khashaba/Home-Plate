@extends('partial.master')

@section('title')

@endsection

@section('content')

<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create</h5>
    </div>

    <div class="mb-5">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="ctnEmail">Email address</label>
                <input
                    id="ctnEmail"
                    type="email"
                    placeholder="name@example.com"
                    class="form-input"
                    required
                />
            </div>

            <div>
                <label for="ctnSelect1">Example select</label>
                <select id="ctnSelect1" class="form-select text-white-dark" required>
                    <option>Open this select menu</option>
                    <option>One</option>
                    <option>Two</option>
                    <option>Three</option>
                </select>
            </div>

            <div>
                <label for="ctnSelect2">Example multiple select</label>
                <select id="ctnSelect2" multiple class="form-multiselect text-white-dark" required>
                    <option>Open this select menu</option>
                    <option>One</option>
                    <option>Two</option>
                    <option>Three</option>
                </select>
            </div>

            <div>
                <label for="ctnTextarea">Example textarea</label>
                <textarea
                    id="ctnTextarea"
                    rows="3"
                    class="form-textarea"
                    placeholder="Enter Address"
                    required
                ></textarea>
            </div>

            <div class="md:col-span-2">
                <label for="ctnFile">Example file input</label>
                <input
                    id="ctnFile"
                    type="file"
                    class="rtl:file-ml-5 form-input p-0 file:border-0 file:bg-primary/90 file:px-4 file:py-2 file:font-semibold file:text-white file:hover:bg-primary ltr:file:mr-5"
                    required
                />
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>



@endsection


@section('scripts')


@endsection
