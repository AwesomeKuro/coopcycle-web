import _ from 'lodash'

import {
  SET_FILTER_VALUE,
  RESET_FILTERS,
  SET_CLUSTERS_ENABLED,
  SET_POLYLINE_STYLE,
  SHOW_RECURRENCE_RULES,
  SET_USE_AVATAR_COLORS,
  SET_TOURS_ENABLED,
} from './actions'

const defaultFilters = {
  showFinishedTasks: true,
  showCancelledTasks: false,
  alwayShowUnassignedTasks: false,
  tags: [],
  hiddenCouriers: [],
  timeRange: [0, 24],
}

export const initialState = {
  filters: defaultFilters,
  isDefaultFilters: true,
  clustersEnabled: false,
  polylineStyle: 'normal',
  isRecurrenceRulesVisible: true,
  useAvatarColors: false,
  toursEnabled: false,
}

export default (state = initialState, action) => {
  switch (action.type) {

  case SHOW_RECURRENCE_RULES:
    return {
      ...state,
      isRecurrenceRulesVisible: action.isChecked
    }

  case SET_FILTER_VALUE:

    const newFilters = {
      ...state.filters,
      [action.key]: action.value
    }

    return {
      ...state,
      filters: newFilters,
      isDefaultFilters: _.isEqual(newFilters, defaultFilters)
    }

  case RESET_FILTERS:

    return {
      ...state,
      filters: defaultFilters,
      isDefaultFilters: true
    }

  case SET_CLUSTERS_ENABLED:

    return {
      ...state,
      clustersEnabled: action.enabled
    }

  case SET_USE_AVATAR_COLORS:

    return {
      ...state,
      useAvatarColors: action.useAvatarColors
    }

  case SET_POLYLINE_STYLE:

    return {
      ...state,
      polylineStyle: action.style
    }

  case SET_TOURS_ENABLED:

    return {
      ...state,
      toursEnabled: action.enabled
    }
  }

  let isDefaultFilters = initialState.isDefaultFilters
  if (Object.prototype.hasOwnProperty.call(state, 'filters') && !Object.prototype.hasOwnProperty.call(state, 'isDefaultFilters')) {
    isDefaultFilters = _.isEqual(state.filters, defaultFilters)
  }

  return {
    ...state,
    filters: Object.prototype.hasOwnProperty.call(state, 'filters') ? state.filters : initialState.filters,
    isDefaultFilters: Object.prototype.hasOwnProperty.call(state, 'isDefaultFilters') ? state.isDefaultFilters : isDefaultFilters,
  }
}
