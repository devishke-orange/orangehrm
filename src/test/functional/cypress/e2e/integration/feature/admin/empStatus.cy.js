/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

describe('Admin - Employment Status', function () {
  beforeEach(function () {
    cy.task('db:truncate', {tables: ['EmploymentStatus']});
    cy.fixture('chars').as('strings');
    cy.intercept('GET', '**/api/v2/admin/employment-statuses*').as(
      'getEmpStatus',
    );
    cy.intercept('POST', '**/api/v2/admin/employment-statuses').as(
      'postEmpStatus',
    );
    cy.intercept('PUT', '**/api/v2/admin/employment-statuses/*').as(
      'putEmpStatus',
    );
    cy.intercept('DELETE', '**/api/v2/admin/employment-statuses').as(
      'deleteEmpStatus',
    );
    cy.fixture('user').then(({admin}) => {
      this.user = admin;
    });
  });

  describe('get emp status list', function () {
    it('load emp status list', function () {
      cy.loginTo(this.user, '/admin/employmentStatus');
      cy.wait('@getEmpStatus');
      cy.toast('info', 'No Records Found');
    });
  });

  describe('create snapshot with emp status', function () {
    it('create snapshot with emp status', function () {
      cy.loginTo(this.user, '/admin/saveEmploymentStatus');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').type(this.strings.chars50.text);
        cy.getOXD('button').contains('Save').click();
      });
      cy.wait('@postEmpStatus').then(function () {
        cy.task('db:snapshotSpecific', {
          savepointName: 'emp-statuses-ui',
          tables: ['ohrm_employment_status'],
        });
      });
    });
  });

  describe('add emp status', function () {
    it('add an emp status and save', function () {
      cy.loginTo(this.user, '/admin/saveEmploymentStatus');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').type(this.strings.chars10.text);
        cy.getOXD('button').contains('Save').click();
      });
      cy.wait('@postEmpStatus');
      cy.toast('success', 'Successfully Saved');
    });

    it('add an emp status and cancel', function () {
      cy.loginTo(this.user, '/admin/saveEmploymentStatus');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').type(this.strings.chars30.text);
        cy.getOXD('button').contains('Cancel').click();
      });
      cy.wait('@getEmpStatus');
      cy.getOXD('pageTitle').should('include.text', 'Employment Status');
    });

    it('add emp status form validations', function () {
      cy.task('db:restoreSpecific', {
        savepointName: 'emp-statuses-ui',
        tables: ['ohrm_employment_status'],
      });
      cy.loginTo(this.user, '/admin/saveEmploymentStatus');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').then(($input) => {
          cy.wrap($input).type(this.strings.chars100.text);
          cy.wrap($input).isInvalid('Should not exceed 50 characters');
          cy.wrap($input).setValue('');
          cy.wrap($input).isInvalid('Required');
          cy.wrap($input).type(this.strings.chars50.text);
          cy.wrap($input).isInvalid('Already exists');
        });
      });
    });
  });

  describe('update emp status', function () {
    it('update emp status', function () {
      cy.task('db:restoreSpecific', {
        savepointName: 'emp-statuses-ui',
        tables: ['ohrm_employment_status'],
      });
      cy.loginTo(this.user, '/admin/employmentStatus');
      cy.wait('@getEmpStatus');
      cy.get(
        '.oxd-table-body > :nth-child(1) .oxd-table-cell-actions > :nth-child(2)',
      ).click();
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').then(($input) => {
          cy.wrap($input).clear();
          cy.wrap($input).type(this.strings.chars30.text);
        });
        cy.getOXD('button').contains('Save').click();
      });
      cy.wait('@putEmpStatus');
      cy.toast('success', 'Successfully Updated');
    });
  });

  describe('delete emp status', function () {
    it('delete emp status', function () {
      cy.task('db:restoreSpecific', {
        savepointName: 'emp-statuses-ui',
        tables: ['ohrm_employment_status'],
      });
      cy.loginTo(this.user, '/admin/employmentStatus');
      cy.wait('@getEmpStatus');
      cy.get(
        '.oxd-table-body > :nth-child(1) .oxd-table-cell-actions > :nth-child(1)',
      ).click();
      cy.getOXD('button').contains('Yes, Delete').click();
      cy.wait('@deleteEmpStatus');
      cy.toast('success', 'Successfully Deleted');
    });
  });
});
