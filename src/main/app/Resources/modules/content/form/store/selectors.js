import {createSelector} from 'reselect'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'

// default form shape to avoid undefined access before reducer mount
const DEFAULT_FORM_STATE = {
  new: false,
  mode: undefined,
  validating: false,
  pendingChanges: false,
  errors: {},
  data: {},
  originalData: {}
}

// retrieves a form instance in the store, falling back to a safe default
const form = (state, formName) => get(state, formName, DEFAULT_FORM_STATE)

const isNew = (formState) => formState.new
const mode = (formState) => formState.mode
const validating = (formState) => formState.validating
const pendingChanges = (formState) => formState.pendingChanges
const errors = (formState) => formState.errors
const data = (formState) => formState.data
const originalData = (formState) => formState.originalData

const value = (formState, prop) => get(data(formState), prop)

const valid = createSelector(
  [errors],
  (errors) => isEmpty(errors)
)

const saveEnabled = createSelector(
  [pendingChanges, validating, valid],
  (pendingChanges, validating, valid) => pendingChanges && (!validating || valid)
)

export const selectors = {
  form,
  isNew,
  mode,
  validating,
  pendingChanges,
  errors,
  data,
  originalData,
  valid,
  saveEnabled,
  value
}
