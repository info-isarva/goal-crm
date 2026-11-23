<?php
    $prefix = $prefix ?? 'add';
    $values = $values ?? [];
    $showErrors = $showErrors ?? false;
    $canApprove = $canApprove ?? false;
    $teamUsers = $teamUsers ?? collect();
    $v = fn ($key, $default = '') => $values[$key] ?? $default;
    $ownerType = $v('owner_type', 'employee');
    $currency = config('app.currency_symbol', '₹');
?>

<div class="gt-form">
    <div class="section-bar"><i class="bi bi-person-badge"></i> Owner</div>
    <div class="gt-owner-toggle" role="radiogroup" aria-label="Target owner type">
        <label class="gt-owner-opt">
            <input class="owner-type-radio" type="radio" name="owner_type"
                   id="<?php echo e($prefix); ?>_owner_employee" value="employee"
                   data-prefix="<?php echo e($prefix); ?>"
                   <?php if($ownerType === 'employee'): echo 'checked'; endif; ?> required>
            <span><i class="bi bi-person"></i> Team member</span>
        </label>
        <label class="gt-owner-opt">
            <input class="owner-type-radio" type="radio" name="owner_type"
                   id="<?php echo e($prefix); ?>_owner_org" value="org_unit"
                   data-prefix="<?php echo e($prefix); ?>"
                   <?php if($ownerType === 'org_unit'): echo 'checked'; endif; ?>>
            <span><i class="bi bi-building"></i> Org unit</span>
        </label>
    </div>
    <?php if($showErrors): ?> <?php $__errorArgs = ['owner_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block mb-2"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-12 owner-employee-wrap" data-prefix="<?php echo e($prefix); ?>" style="<?php echo e($ownerType === 'employee' ? '' : 'display:none'); ?>">
            <label class="form-label" for="<?php echo e($prefix); ?>_user_id">Team member <span class="req">*</span></label>
            <select class="form-select <?php if($showErrors && $errors->has('user_id')): ?> is-invalid <?php endif; ?>"
                    id="<?php echo e($prefix); ?>_user_id" name="user_id"
                    data-search="true" data-allow-clear="true" data-placeholder="Select person…">
                <option value="">Select person…</option>
                <?php $__currentLoopData = $teamUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($u->id); ?>" <?php if((string) $v('user_id') === (string) $u->id): echo 'selected'; endif; ?>>
                        <?php echo e($u->name); ?><?php echo e($u->job_role ? ' ('.strtoupper($u->job_role).')' : ''); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php if($showErrors): ?> <?php $__errorArgs = ['user_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>

        <div class="col-12 owner-org-wrap" data-prefix="<?php echo e($prefix); ?>" style="<?php echo e($ownerType === 'org_unit' ? '' : 'display:none'); ?>">
            <label class="form-label" for="<?php echo e($prefix); ?>_goal_org_unit_id">Org unit <span class="req">*</span></label>
            <select class="form-select <?php if($showErrors && $errors->has('goal_org_unit_id')): ?> is-invalid <?php endif; ?>"
                    id="<?php echo e($prefix); ?>_goal_org_unit_id" name="goal_org_unit_id"
                    data-search="true" data-allow-clear="true" data-placeholder="Select region / cluster / branch…">
                <option value="">Select region / cluster / branch…</option>
                <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($b->id); ?>" <?php if((string) $v('goal_org_unit_id') === (string) $b->id): echo 'selected'; endif; ?>>
                        <?php echo e(strtoupper($b->type)); ?> · <?php echo e($b->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php if($showErrors): ?> <?php $__errorArgs = ['goal_org_unit_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
    </div>

    <div class="section-bar"><i class="bi bi-calendar3"></i> Period & metric</div>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label" for="<?php echo e($prefix); ?>_period">Period <span class="req">*</span></label>
            <input type="text" class="form-control js-monthpicker <?php if($showErrors && $errors->has('period')): ?> is-invalid <?php endif; ?>"
                   id="<?php echo e($prefix); ?>_period" name="period" value="<?php echo e($v('period')); ?>"
                   placeholder="Select month" autocomplete="off" required>
            <?php if($showErrors): ?> <?php $__errorArgs = ['period'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="<?php echo e($prefix); ?>_metric">Metric <span class="req">*</span></label>
            <select class="form-select <?php if($showErrors && $errors->has('metric')): ?> is-invalid <?php endif; ?>"
                    id="<?php echo e($prefix); ?>_metric" name="metric" required
                    data-search="false" data-allow-clear="false" data-placeholder="Select metric">
                <option value="revenue" <?php if($v('metric') === 'revenue'): echo 'selected'; endif; ?>>Revenue</option>
                <option value="disbursement" <?php if($v('metric') === 'disbursement'): echo 'selected'; endif; ?>>Disbursement</option>
            </select>
            <?php if($showErrors): ?> <?php $__errorArgs = ['metric'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="<?php echo e($prefix); ?>_status">Status</label>
            <?php if($canApprove): ?>
                <select class="form-select <?php if($showErrors && $errors->has('status')): ?> is-invalid <?php endif; ?>"
                        id="<?php echo e($prefix); ?>_status" name="status"
                        data-search="false" data-allow-clear="false">
                    <option value="Draft" <?php if($v('status', 'Draft') === 'Draft'): echo 'selected'; endif; ?>>Draft</option>
                    <option value="Approved" <?php if($v('status') === 'Approved'): echo 'selected'; endif; ?>>Approved</option>
                </select>
            <?php else: ?>
                <input type="hidden" name="status" value="Draft">
                <input type="text" class="form-control" id="<?php echo e($prefix); ?>_status" value="Draft" disabled
                       title="BM creates as Draft. Cluster+ can approve.">
            <?php endif; ?>
            <?php if($showErrors): ?> <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
    </div>

    <div class="section-bar"><i class="bi bi-currency-rupee"></i> Amounts</div>
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label" for="<?php echo e($prefix); ?>_base_target">Base Target <span class="req">*</span></label>
            <div class="input-icon">
                <span class="rupee"><?php echo e($currency); ?></span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal <?php if($showErrors && $errors->has('base_target')): ?> is-invalid <?php endif; ?>"
                       id="<?php echo e($prefix); ?>_base_target" name="base_target" value="<?php echo e($v('base_target')); ?>" placeholder="0.00" required>
            </div>
            <?php if($showErrors): ?> <?php $__errorArgs = ['base_target'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="<?php echo e($prefix); ?>_opening_shortfall">
                Opening Shortfall
                <i class="bi bi-info-circle gt-tip" title="Auto-filled from prior closing shortfall when left 0 on create"></i>
            </label>
            <div class="input-icon">
                <span class="rupee"><?php echo e($currency); ?></span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal <?php if($showErrors && $errors->has('opening_shortfall')): ?> is-invalid <?php endif; ?>"
                       id="<?php echo e($prefix); ?>_opening_shortfall" name="opening_shortfall" value="<?php echo e($v('opening_shortfall', '0')); ?>" placeholder="0.00">
            </div>
            <?php if($showErrors): ?> <?php $__errorArgs = ['opening_shortfall'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="<?php echo e($prefix); ?>_adjustments">Adjustments</label>
            <div class="input-icon">
                <span class="rupee"><?php echo e($currency); ?></span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal <?php if($showErrors && $errors->has('adjustments')): ?> is-invalid <?php endif; ?>"
                       id="<?php echo e($prefix); ?>_adjustments" name="adjustments" value="<?php echo e($v('adjustments', '0')); ?>" placeholder="0.00">
            </div>
            <?php if($showErrors): ?> <?php $__errorArgs = ['adjustments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="<?php echo e($prefix); ?>_actual">Actual</label>
            <div class="input-icon">
                <span class="rupee"><?php echo e($currency); ?></span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal <?php if($showErrors && $errors->has('actual')): ?> is-invalid <?php endif; ?>"
                       id="<?php echo e($prefix); ?>_actual" name="actual" value="<?php echo e($v('actual', '0')); ?>" placeholder="0.00">
            </div>
            <?php if($showErrors): ?> <?php $__errorArgs = ['actual'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
        </div>
    </div>

    <div class="section-bar"><i class="bi bi-calculator"></i> Live calculation</div>
    <div class="calc-box" title="Effective = Base + Opening SF + Adjustments · Closing SF = max(0, Effective − Actual)">
        <div class="calc-item">
            <div class="calc-label">Effective Target</div>
            <div class="calc-value" id="<?php echo e($prefix); ?>_calc_effective"><?php echo e($currency); ?> 0.00</div>
        </div>
        <div class="calc-item">
            <div class="calc-label">Closing Shortfall</div>
            <div class="calc-value is-sf" id="<?php echo e($prefix); ?>_calc_closing"><?php echo e($currency); ?> 0.00</div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/goal/targets/_form_fields.blade.php ENDPATH**/ ?>