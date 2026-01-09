<div class="loan-stage-selection">
    <div class="alert alert-info">
        <strong>{{opportunityName}}</strong> → <strong>{{targetStage}}</strong>
        <br><small>Select the loan stage:</small>
    </div>
    
    <div class="loan-stage-options">
        {{#each allowedLoanStages}}
        <div class="loan-stage-option panel panel-default" data-stage="{{this}}" style="cursor: pointer; margin-bottom: 8px;">
            <div class="panel-body" style="padding: 12px 15px;">
                <span class="fas fa-chevron-right pull-right text-muted" style="margin-top: 3px;"></span>
                <strong>{{this}}</strong>
            </div>
        </div>
        {{/each}}
    </div>
</div>

<style>
.loan-stage-option:hover {
    background-color: #f5f5f5;
    border-color: #337ab7;
}
</style>
