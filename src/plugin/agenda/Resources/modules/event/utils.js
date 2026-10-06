import moment from 'moment'

import {trans, tval} from '#/main/app/intl/translation'
import {displayDateRange} from '#/main/app/intl/date'

function eventDuration(event) {
  if (event.allDay) {
    // TODO : correct compute
    return trans('all_day', {}, 'agenda')
  }

  return moment(event.start).format('LT')
}

function sortEvents(events) {
  return events.sort((a, b) => {
    // TODO : correct compute
    if (a.allDay && !b.allDay) {
      return -1
    } else if (!a.allDay && b.allDay) {
      return 1
    }

    if (a.start < b.start) {
      return -1
    } else if (a.start > b.start) {
      return 1
    }

    return 0
  })
}

/**
 * Champ « dates » commun aux formulaires d'évènement et de tâche.
 *
 * - début et fin sont obligatoires (le `required` du formulaire accepte [début, null]) ;
 * - le début se choisit librement, la fin le suit s'il la dépasse ;
 * - l'aide rappelle la plage en toutes lettres, pour repérer une date restée sur
 *   aujourd'hui sans imposer d'étape de confirmation.
 *
 * @param {object}   event  - les données courantes du formulaire
 * @param {function} update - met à jour une propriété du formulaire
 *
 * @return {object}
 */
function eventDatesField(event, update) {
  return {
    name: 'dates',
    type: 'date-range',
    label: trans('date'),
    required: true,
    help: event && event.start && event.end ? displayDateRange(event.start, event.end) : undefined,
    calculated: (data) => [data.start || null, data.end || null],
    onChange: (datesRange) => {
      update('start', datesRange[0])
      update('end', datesRange[1])
    },
    validate: (datesRange) => {
      if (!datesRange[0] || !datesRange[1]) {
        return [
          datesRange[0] ? undefined : tval('valid_start_date_required'),
          datesRange[1] ? undefined : tval('valid_end_date_required')
        ]
      }

      return undefined
    },
    options: {
      time: true,
      endFollowsStart: true
    }
  }
}

export {
  sortEvents,
  eventDuration,
  eventDatesField
}