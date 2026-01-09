define('custom:views/opportunity/modals/select-loan-stage', ['views/modal'], function (Dep) {

    return Dep.extend({

        template: 'custom:opportunity/modals/select-loan-stage',
        backdrop: true,
        cssName: 'select-loan-stage',

        data: function () {
            return {
                targetStage: this.options.targetStage,
                allowedLoanStages: this.options.allowedLoanStages,
                opportunityName: this.model.get('name')
            };
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];
            this.headerText = this.translate('Select Loan Stage', 'labels', 'Opportunity');
            this.model = this.options.model;
            this.targetStage = this.options.targetStage;
            this.allowedLoanStages = this.options.allowedLoanStages || [];
        },

        afterRender: function () {
            var self = this;
            this.$el.find('.loan-stage-option').on('click', function () {
                var selectedStage = $(this).data('stage');
                self.trigger('select', selectedStage);
            });
        },

        actionCancel: function () {
            this.trigger('cancel');
        }
    });
});
