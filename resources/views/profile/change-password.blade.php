 @extends('layouts.app')

                @section('content')
                <div class="container-fluid px-4">
                    <div class="row ">
                        <div class="col-lg-12 col-md-10">
                            <div class="card shadow-lg-0 border-0 p-4 mb-4" style="background:transparent;">
                                <div class="d-flex align-items-center mb-4">
                                    <span class="avatar rounded-circle bg-white border d-inline-flex align-items-center justify-content-center me-3" style="width:56px;height:56px;font-size:2rem;">
                                        <i class="bi bi-person-circle text-primary"></i>
                                    </span>
                                    <div>
                                        <h2 class="mb-0 fw-bold">Change Password</h2>
                                        <div class="text-muted small">Update your account information and password</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <!-- <h5 class="fw-semibold mb-3"><i class="bi bi-key text-primary me-2"></i> Change Password</h5> -->
                                    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                                        @include('profile.partials.update-password-form')
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                @endsection
