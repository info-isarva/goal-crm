<?php $__env->startSection('content'); ?>
    <?php
        $company = \App\Models\Company::current();
        $companyName = !empty($company->name) ? $company->name : 'Isarva CRM';
        $daysRemaining = null;
        $isAppExpired = false;
        if (!empty($company->expire_date)) {
            $today = \Carbon\Carbon::today();
            $expireDate = \Carbon\Carbon::parse($company->expire_date)->startOfDay();
            $daysRemaining = (int) $today->diffInDays($expireDate, false);
            $isAppExpired = \Carbon\Carbon::parse($company->expire_date)->endOfDay()->isPast();
        }
    ?>
    <link rel="stylesheet" href="/css/login-custom.css?v=2">
    <div class="login-bg-gradient">

        <div class="login-card-container">
            <div class="login-form-section">
                <div class="login-logo-wrap text-center mb-3">
                    <a href="<?php echo e(url('/')); ?>" class="login-logo-link" aria-label="<?php echo e($companyName); ?> home">
                        <img src="<?php echo e($company->logo_url); ?>" alt="<?php echo e($companyName); ?>" class="login-logo"
                            style="max-height: 60px; object-fit: contain;">
                        <span class="visually-hidden"><?php echo e($companyName); ?></span>
                    </a>
                </div>
                <h4 class="text-dark mb-3 text-center fw-semibold">Sign In to your Account</h4>

                <?php if($isAppExpired): ?>
                    <div class="alert alert-danger text-center small mb-3 p-2" style="border-radius: 12px;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Application subscription expired on
                        <strong><?php echo e(\Carbon\Carbon::parse($company->expire_date)->format('d M Y')); ?></strong>. Non-admin access
                        is restricted.
                    </div>
                <?php elseif($daysRemaining !== null && $daysRemaining >= 0 && $daysRemaining <= 5): ?>
                    <div class="alert alert-warning text-center small mb-3 p-2"
                        style="background-color: #fff3cd; color: #664d03; border-color: #ffecb5; border-radius: 12px;">
                        <i class="fas fa-exclamation-circle me-1"></i> Notice: Application setup will expire
                        <strong><?php echo e($daysRemaining == 0 ? 'today' : ($daysRemaining == 1 ? 'in 1 day' : 'within ' . $daysRemaining . ' days')); ?></strong>
                        (on <?php echo e(\Carbon\Carbon::parse($company->expire_date)->format('d M Y')); ?>).
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('login')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 1.5rem 0 0 1.5rem;">
                                <i class="fa fa-user text-secondary"></i>
                            </span>
                            <input type="email" id="email" name="email"
                                class="form-control rounded-end-pill <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                placeholder="Enter Email" required autofocus style="border-radius: 0 1.5rem 1.5rem 0;">
                        </div>

                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label mb-0">Password</label>

                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 1.5rem 0 0 1.5rem;">
                                <i class="fa fa-lock text-secondary"></i>
                            </span>
                            <input type="password" id="password" name="password"
                                class="form-control rounded-end-pill <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                placeholder="Enter Password" required style="border-radius: 0 1.5rem 1.5rem 0;">
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-check mb-3">
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <input type="checkbox" class="form-check-input" name="remember" id="remember" value="1" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="remember" style="cursor: pointer;">Remember this
                                    device</label>
                            </div>
                            <a href="<?php echo e(route('password.request')); ?>" class="small text-decoration-none forgot-link">Forgot
                                password?</a>
                        </div>

                    </div>
                    <button type="submit" class="btn btn-cta w-100 fw-semibold">
                        SIGN IN
                    </button>

                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback d-block text-center"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </form>
                
                <hr class="my-3">
                <p class="text-center small mb-2">Or continue with</p>
                <div class="d-grid gap-2">
                    <a href="<?php echo e(route('google.login')); ?>" class="btn btn-google-outline w-100 fw-semibold">
                        <i class="fab fa-google me-2 google-icon-colored"></i>
                        SIGN IN WITH GOOGLE
                    </a>
                </div>
            </div>
            <div class="login-image-section d-flex flex-column justify-content-center align-items-center position-relative">
                <div class="login-hero-text w-100 text-center" style="z-index:2; position:static;">
                    <h1 class="fw-bold mb-2 login-hero-title">CRM Workspace</h1>
                    <div class="mb-4 login-hero-subtitle">Manage leads, contacts and deals from a single place</div>
                </div>

                
                <div class="login-features mt-2" style="z-index:2;">
                    <!-- <h2 class="fw-bold login-features-title">CRM Workspace</h2>
                    <p class="login-features-subtitle small mb-3">Manage leads, contacts and deals from a single place</p> -->

                    <div class="feature-list">
                        <div class="feature-card d-flex align-items-center mb-3">
                            <div class="feature-icon me-3"><i class="fa fa-bullhorn"></i></div>
                            <div>
                                <div class="fw-semibold text-color">Lead Management</div>
                                <div class="small text-muted text-color">Capture, qualify and nurture leads with automation
                                </div>
                            </div>
                        </div>

                        <div class="feature-card d-flex align-items-center mb-3">
                            <div class="feature-icon me-3"><i class="fa fa-address-book"></i></div>
                            <div>
                                <div class="fw-semibold text-color">Contacts & Accounts</div>
                                <div class="small text-muted text-color">Centralize customer data and interaction history
                                </div>
                            </div>
                        </div>

                        <div class="feature-card d-flex align-items-center mb-0">
                            <div class="feature-icon me-3"><i class="fa fa-chart-line"></i></div>
                            <div>
                                <div class="fw-semibold text-color">Sales Pipeline & Reports</div>
                                <div class="small text-muted text-color">Track deals, forecast revenue and measure
                                    performance</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/auth/login.blade.php ENDPATH**/ ?>