{{-- Add New Industry modal (lead/deal create & edit) --}}
<div class="modal fade" id="industryNewModal" tabindex="-1" aria-labelledby="industryNewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="industryNewForm" autocomplete="off">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="industryNewModalLabel">Add New Industries</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="industry_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" placeholder="Industry name" class="form-control" id="industry_name" name="name" maxlength="100">
                        <div id="industry_name_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="industry_description" class="form-label">Description</label>
                        <textarea placeholder="Optional description" class="form-control" id="industry_description" name="description" rows="3" maxlength="250"></textarea>
                        <div id="industry_description_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
