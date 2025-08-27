import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'

import {trans} from '#/main/app/intl'
import {Routes} from '#/main/app/router/components/routes'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ContentTabs} from '#/main/app/content/components/tabs'

import {
  Course as CourseTypes,
  Session as SessionTypes
} from '#/plugin/cursus/prop-types'
import {route} from '#/plugin/cursus/routing'
import {CourseAbout} from '#/plugin/cursus/course/containers/about'
import {CourseCancellations} from '#/plugin/cursus/course/components/cancellations'
import {CourseSessions} from '#/plugin/cursus/course/containers/sessions'
import {CourseEvents} from '#/plugin/cursus/course/containers/events'
import {CoursePendings} from '#/plugin/cursus/course/containers/pendings'
import {CoursePresences} from '#/plugin/cursus/course/containers/presences'
import {CourseTutors} from '#/plugin/cursus/course/containers/tutors'

const CourseDetails = (props) =>
  <Fragment>
    <header className="row content-heading">
      <ContentTabs
        backAction={{
          type: LINK_BUTTON,
          target: props.path,
          exact: true
        }}
        sections={[
          {
            name: 'about',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-info',
            label: props.activeSession ? trans('session_about', {}, 'cursus') : trans('about'),
            target: route(props.path, props.course, props.activeSession),
            exact: true
          }, {
            name: 'sessions',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-calendar-week',
            label: trans('sessions', {}, 'cursus'),
            target: `${route(props.path, props.course, props.activeSession)}/sessions`,
            displayed: !get(props.course, 'display.hideSessions')
          }, {
            name: 'events',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-clock',
            label: trans('session_events', {}, 'cursus'),
            target: `${route(props.path, props.course, props.activeSession)}/events`,
            displayed: !!props.activeSession
          }, {
            name: 'tutors',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-chalkboard-teacher',
            label: trans('tutors', {}, 'cursus'),
            target: `${route(props.path, props.course, props.activeSession)}/tutors`,
            displayed: props.canValidateRegistrations && !!props.activeSession
          }, {
            name: 'pendings',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-hourglass-half',
            label: trans('pendings', {}, 'cursus'),
            displayed: props.canValidateRegistrations && !!props.activeSession,
            target: `${route(props.path, props.course, props.activeSession)}/pendings`
          }, {
            name: 'presences',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-users',
            label: trans('presences_validation', {}, 'cursus'),
            target: `${route(props.path, props.course, props.activeSession)}/presences`,
            displayed: (props.canValidateRegistrations || props.canValidatePresences) && !!props.activeSession,
          }, {
            name: 'cancellations',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-ban',
            label: trans('cancellations', {}, 'cursus'),
            target: `${route(props.path, props.course, props.activeSession)}/cancellations`,
            displayed: (props.canValidateRegistrations || props.canValidatePresences) && !!props.activeSession,
          }
        ]}
      />
    </header>

    <Routes
      path={route(props.path, props.course, props.activeSession)}
      routes={[
        {
          path: '/',
          exact: true,
          render() {
            return (
              <CourseAbout
                path={props.path}
                course={props.course}
                activeSession={props.activeSession}
                activeSessionRegistration={props.activeSessionRegistration}
                courseRegistration={props.courseRegistration}
                availableSessions={props.availableSessions}
              />
            )
          }
        }, {
          path: '/sessions',
          disabled: get(props.course, 'display.hideSessions', false),
          render() {
            return (
              <CourseSessions
                path={props.path}
                course={props.course}
              />
            )
          }
        }, {
          path: '/events',
          disabled: !props.activeSession,
          render() {
            return (
              <CourseEvents
                path={props.path}
                course={props.course}
                activeSession={props.activeSession}
              />
            )
          }
        }, {
          path: '/tutors',
          disabled: !props.activeSession || !(props.canValidateRegistrations || props.canValidatePresences),
          render() {
            return (
              <CourseTutors
                path={props.path}
                course={props.course}
                activeSession={props.activeSession}
              />
            )
          }
        }, {
          path: '/pendings',
          disabled: !props.activeSession || !(props.canValidateRegistrations || props.canValidatePresences),
          render() {
            return (
              <CoursePendings
                path={props.path}
                course={props.course}
                activeSession={props.activeSession}
              />
            )
          }
        }, {
          path: '/presences',
          disabled: !props.activeSession || !(props.canValidateRegistrations || props.canValidatePresences),
          render() {
            return (
              <CoursePresences
                path={props.path}
                course={props.course}
                activeSession={props.activeSession}
              />
            )
          }
        }, {
          path: '/cancellations',
          disabled: !props.activeSession || !(props.canValidateRegistrations || props.canValidatePresences),
          render() {
            return (
              <CourseCancellations
                path={props.path}
                course={props.course}
                activeSession={props.activeSession}
              />
            )
          }
        }
      ]}
    />
  </Fragment>

CourseDetails.propTypes = {
  path: T.string.isRequired,
  isAuthenticated: T.bool.isRequired,
  canValidateRegistrations: T.bool.isRequired,
  canValidatePresences: T.bool.isRequired,
  course: T.shape(
    CourseTypes.propTypes
  ).isRequired,
  activeSession: T.shape(
    SessionTypes.propTypes
  ),
  activeSessionRegistration: T.shape({
  }),
  courseRegistration: T.shape({

  }),
  availableSessions: T.arrayOf(T.shape(
    SessionTypes.propTypes
  ))
}

export {
  CourseDetails
}
