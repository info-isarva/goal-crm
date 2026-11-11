<style>
.qt-wizard-stepper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.25rem;
    padding: 1.25rem 1rem 0.75rem;
    margin-bottom: 1.25rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow-x: auto;
}
.qt-wizard-form-wrap {
    position: relative;
}
.qt-wizard-stepper-item {
    flex: 1 1 0;
    min-width: 72px;
    text-align: center;
    position: relative;
    cursor: pointer;
    user-select: none;
}
.qt-wizard-stepper-item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 16px;
    left: calc(50% + 18px);
    right: calc(-50% + 18px);
    height: 2px;
    background: #d1d5db;
    z-index: 0;
}
.qt-wizard-stepper-item.is-complete:not(:last-child)::after,
.qt-wizard-stepper-item.is-active:not(:last-child)::after {
    background: #3b82f6;
}
.qt-wizard-step-num {
    width: 32px;
    height: 32px;
    margin: 0 auto 0.4rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    background: #e5e7eb;
    position: relative;
    z-index: 1;
}
.qt-wizard-stepper-item.is-active .qt-wizard-step-num {
    background: #3b82f6;
    color: #fff;
}
.qt-wizard-stepper-item.is-complete .qt-wizard-step-num {
    background: #3b82f6;
    color: #fff;
}
.qt-wizard-step-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}
.qt-wizard-stepper-item.is-active .qt-wizard-step-label {
    color: #111827;
    font-weight: 600;
}
.qt-wizard-panel {
    display: none;
}
.qt-wizard-panel.is-active {
    display: block;
}
.qt-wizard-panel-title {
    font-size: 1.05rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #111827;
}
.qt-wizard-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}
.qt-wizard-nav .btn-next {
    margin-left: auto;
}
.qt-review-box {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
    margin-bottom: 0.75rem;
    background: #f9fafb;
}
.qt-review-box h6 {
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.qt-review-box .qt-review-row {
    display: flex;
    gap: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}
.qt-review-box .qt-review-row span:first-child {
    color: #6b7280;
    min-width: 140px;
}
.qt-review-preview-wrap {
    position: relative;
    border: 1px solid #dbe3ee;
    border-radius: 0.75rem;
    background: #eef2f6;
    overflow: hidden;
    min-height: 520px;
}
.qt-review-preview-iframe {
    display: block;
    width: 100%;
    height: min(72vh, 860px);
    min-height: 520px;
    border: 0;
    background: #fff;
}
.qt-review-preview-loading {
    position: absolute;
    inset: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,.92);
    color: #475569;
    font-size: 0.9rem;
}
.qt-review-preview-loading.is-hidden {
    display: none;
}
.qt-wizard-panel .is-invalid {
    border-color: #dc3545 !important;
}
.qt-step-error-banner {
    margin-bottom: 1rem;
}
</style>
