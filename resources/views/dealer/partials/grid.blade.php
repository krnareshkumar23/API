<div class="content-container-grid"></div>

<div id="template-vehicle" class="template">
    <div class="row vehicle">
        <div class="col-xs-2 col-no-padding images-input">
            <div class="vehicle-input add-image-input">+</div>
        </div>
        <div class="col-xs-3 col-no-padding">
            <select class="vehicle-input make-input vehicle-input-with-placeholder">
                <option value="-1">Make</option>
            </select>
            <select class="vehicle-input model-input vehicle-input-with-placeholder">
                <option value="-1">Model</option>
            </select>
            <textarea placeholder="Description" class="vehicle-input description-input"></textarea>
        </div>
        <div class="col-xs-2 col-no-padding">
            <input type="number" placeholder="Price (£)" class="vehicle-input price-input">
            <input type="text" placeholder="Mileage" class="vehicle-input mileage-input">
            <div class="details-input-1"></div>
        </div>
        <div class="col-xs-2 col-no-padding details-input-2">
        </div>
        <div class="col-xs-2 col-no-padding">
            <input type="text" placeholder="Email" class="vehicle-input email-input">
            <input type="text" placeholder="Call Number" class="vehicle-input call-input">
            <input type="text" placeholder="SMS Number" class="vehicle-input sms-input">
        </div>
        <div class="col-xs-1 col-no-padding">
            <a class="btn btn-save btn-primary vehicle-save-input">Save</a>
            <a class="btn btn-save btn-disabled vehicle-saved-input">Saved</a>
            <a class="btn btn-save btn-danger btn-sm vehicle-delete-input">Delete</a>
        </div>
    </div>
</div>

<div id="template-vehicle-image" class="template">
    <div class="vehicle-input existing-image-input">
        <div class="existing-image-delete">X</div>
    </div>
</div>

<div id="template-vehicle-detail-select" class="template">
    <select class="vehicle-input vehicle-input-with-placeholder">
    </select>
</div>