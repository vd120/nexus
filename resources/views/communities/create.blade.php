@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-4">
                    <h3 class="fw-bold">Create New Chat Group</h3>
                    <p class="text-muted">Start a private conversation with multiple people.</p>
                </div>
                <div class="card-body pb-4">
                    <form action="{{ route('communities.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Group Name</label>
                            <input type="text" name="name" id="name" class="form-control" required placeholder="Enter group name">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="What is this group about?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Group Avatar</label>
                            <input type="file" name="avatar" id="avatar" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_private" id="is_private" checked>
                                <label class="form-check-label" for="is_private">Private Group (Only invited members can join)</label>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Create Group</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
