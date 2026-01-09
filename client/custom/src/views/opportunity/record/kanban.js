define('custom:views/opportunity/record/kanban', ['views/record/kanban'], function (Dep) {

    return Dep.extend({

        stageLoanStageMap: null,

        setup: function () {
            Dep.prototype.setup.call(this);
            this.stageLoanStageMap = this.getMetadata().get('entityDefs.Opportunity.stageLoanStageMap') || {};
        },

        moveOverItem: function (model, group) {
            var currentStage = model.get('stage');
            var targetStage = group;
            var currentLoanStage = model.get('cCLoanStage');
            
            if (currentStage === targetStage) {
                Dep.prototype.moveOverItem.call(this, model, group);
                return;
            }
            
            var allowedLoanStages = this.stageLoanStageMap[targetStage] || [];
            
            if (allowedLoanStages.length === 0) {
                Dep.prototype.moveOverItem.call(this, model, group);
                return;
            }
            
            // If only one option, auto-select it
            if (allowedLoanStages.length === 1) {
                model.set({
                    'stage': targetStage,
                    'cCLoanStage': allowedLoanStages[0]
                });
                model.save(null, {patch: true}).then(() => {
                    Espo.Ui.success(this.translate('Saved'));
                    this.collection.fetch();
                });
                return;
            }
            
            // If current loan stage is valid in target, proceed
            if (currentLoanStage && allowedLoanStages.includes(currentLoanStage)) {
                Dep.prototype.moveOverItem.call(this, model, group);
                return;
            }
            
            // Show modal to select loan stage
            this.showLoanStageModal(model, targetStage, allowedLoanStages);
        },

        showLoanStageModal: function (model, targetStage, allowedLoanStages) {
            var self = this;
            
            this.createView('loanStageModal', 'custom:views/opportunity/modals/select-loan-stage', {
                model: model,
                targetStage: targetStage,
                allowedLoanStages: allowedLoanStages
            }, function (view) {
                view.render();
                
                self.listenToOnce(view, 'select', function (selectedLoanStage) {
                    view.close();
                    model.set({
                        'stage': targetStage,
                        'cCLoanStage': selectedLoanStage
                    });
                    model.save(null, {patch: true}).then(function () {
                        Espo.Ui.success(self.translate('Saved'));
                        self.collection.fetch();
                    }).catch(function () {
                        Espo.Ui.error(self.translate('Error'));
                        self.collection.fetch();
                    });
                });
                
                self.listenToOnce(view, 'cancel', function () {
                    view.close();
                    self.collection.fetch();
                });
            });
        }
    });
});
