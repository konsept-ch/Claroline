import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import {schemeCategory20c} from 'd3-scale'

import {trans} from '#/main/app/intl/translation'
import {hasPermission} from '#/main/app/security'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {constants as listConst} from '#/main/app/content/list/constants'
import {ListData} from '#/main/app/content/list/containers/data'
import {Routes} from '#/main/app/router/components/routes'
import {Vertical} from '#/main/app/content/tabs/components/vertical'
import {ContentCounter} from '#/main/app/content/components/counter'
import {MODAL_USERS} from '#/main/community/modals/users'
import {MODAL_GROUPS} from '#/main/community/modals/groups'
import {UserCard} from '#/main/community/user/components/card'

import {selectors} from '#/plugin/cursus/tools/trainings/catalog/store/selectors'
import {Course as CourseTypes, Session as SessionTypes} from '#/plugin/cursus/prop-types'
import {constants} from '#/plugin/cursus/constants'

import {CourseStats} from '#/plugin/cursus/course/components/stats'
import {SessionGroups} from '#/plugin/cursus/session/containers/groups'
import {SessionUsers} from '#/plugin/cursus/session/containers/users'
import {Button} from '#/main/app/action'
import {MODAL_SESSIONS} from '#/plugin/cursus/modals/sessions'

const CourseParticipants = (props) =>
  <Fragment>
    <div className="row" style={{marginTop: -20}}>
      <ContentCounter
        icon="fa fa-chalkboard-teacher"
        label={trans('tutors', {}, 'cursus')}
        color={schemeCategory20c[1]}
        value={get(props.course, 'participants.tutors', 0)}
      />

      <ContentCounter
        icon="fa fa-user"
        label={trans('users')}
        color={schemeCategory20c[5]}
        value={get(props.course, 'participants.learners', 0)}
      />

      <ContentCounter
        icon="fa fa-hourglass-half"
        label={trans('En attente')}
        color={schemeCategory20c[9]}
        value={get(props.course, 'participants.pending', 0)}
      />

      <ContentCounter
        icon="fa fa-user-plus"
        label={trans('occupation', {}, 'cursus')}
        color={schemeCategory20c[13]}
        value={get(props.activeSession, 'restrictions.users') ?
          get(props.activeSession, 'participants.learners', 0) + ' / ' + get(props.activeSession, 'restrictions.users')
          : <span className="fa fa-fw fa-infinity" />
        }
      />
    </div>

    <div className="row">
      <div className="col-md-3">
        <Vertical
          basePath={props.path}
          tabs={[
            {
              icon: 'fa fa-fw fa-chalkboard-teacher',
              title: trans('tutors', {}, 'cursus'),
              path: '/tutors'
            }, {
              icon: 'fa fa-fw fa-user',
              title: trans('users'),
              path: '/',
              exact: true
            }, {
              icon: 'fa fa-fw fa-users',
              title: trans('groups'),
              path: '/groups'
            }, {
              icon: 'fa fa-fw fa-hourglass-half',
              title: trans('Refus RH'),
              path: '/pending',
              displayed: hasPermission('register', props.activeSession)
            }, {
              icon: 'fa fa-fw fa-ban',
              title: trans('cancellations', {}, 'cursus'),
              path: '/cancellations',
              displayed: hasPermission('register', props.activeSession)
            }, {
              icon: 'fa fa-fw fa-pie-chart',
              title: trans('statistics'),
              path: '/stats',
              displayed: !!get(props.course, 'registration.form')
            }
          ]}
        />

        {props.activeSession &&
          <Button
            className="btn btn-link btn-block"
            type={CALLBACK_BUTTON}
            label="Voir pour la session ouverte"
            callback={props.toggleVisibility}
            primary={true}
          />
        }
      </div>

      <div className="col-md-9">
        <Routes
          path={props.path}
          routes={[
            {
              path: '/',
              exact: true,
              render: () => (
                <SessionUsers
                  type={constants.TEACHER_TYPE}
                  course={props.course}
                  name={selectors.STORE_NAME+'.sessionTutors'}
                  customDefinition={[
                    {
                      name: 'session',
                      label: trans('session', {}, 'cursus'),
                      type: 'training_session',
                      displayed: true,
                      displayable: true,
                      filterable: true,
                      options: {
                        course: props.course,
                        picker: {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}]
                        }
                      }
                    }
                  ]}
                  add={{
                    name: 'add_tutors',
                    type: MODAL_BUTTON,
                    label: trans('add_tutors', {}, 'cursus'),
                    modal: [MODAL_USERS, {
                      selectAction: (selected) => ({
                        type: MODAL_BUTTON,
                        label: trans('register', {}, 'actions'),
                        modal: [MODAL_SESSIONS, {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}],
                          selectAction: (selectedSessions) => ({
                            type: CALLBACK_BUTTON,
                            label: trans('register', {}, 'actions'),
                            callback: () => selectedSessions.map(selectedSession => props.addUsers(selectedSession.id, selected, constants.TEACHER_TYPE))
                          })
                        }]
                      })
                    }]
                  }}
                />
              )
            }, {
              path: '/users',
              render: () => (
                <SessionUsers
                  type={constants.LEARNER_TYPE}
                  course={props.course}
                  name={selectors.STORE_NAME+'.sessionUsers'}
                  customDefinition={[
                    {
                      name: 'session',
                      label: trans('session', {}, 'cursus'),
                      type: 'training_session',
                      displayed: true,
                      displayable: true,
                      filterable: true,
                      options: {
                        course: props.course,
                        picker: {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}]
                        }
                      }
                    }
                  ]}
                  add={{
                    name: 'add_users',
                    type: MODAL_BUTTON,
                    label: trans('add_users'),
                    modal: [MODAL_USERS, {
                      selectAction: (selected) => ({
                        type: MODAL_BUTTON,
                        label: trans('register', {}, 'actions'),
                        modal: [MODAL_SESSIONS, {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}],
                          selectAction: (selectedSessions) => ({
                            type: CALLBACK_BUTTON,
                            label: trans('register', {}, 'actions'),
                            callback: () => selectedSessions.map(selectedSession => props.addUsers(selectedSession.id, selected, constants.LEARNER_TYPE))
                          })
                        }]
                      })
                    }]
                  }}
                />
              )
            }, {
              path: '/groups',
              render: () => (
                <SessionGroups
                  type={constants.LEARNER_TYPE}
                  course={props.course}
                  name={selectors.STORE_NAME+'.sessionGroups'}
                  customDefinition={[
                    {
                      name: 'session',
                      label: trans('session', {}, 'cursus'),
                      type: 'training_session',
                      displayed: true,
                      displayable: true,
                      filterable: true,
                      options: {
                        course: props.course,
                        picker: {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}]
                        }
                      }
                    }
                  ]}
                  add={{
                    name: 'add_groups',
                    type: MODAL_BUTTON,
                    label: trans('add_groups'),
                    modal: [MODAL_GROUPS, {
                      selectAction: (selected) => ({
                        type: MODAL_BUTTON,
                        label: trans('register', {}, 'actions'),
                        modal: [MODAL_SESSIONS, {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}],
                          selectAction: (selectedSessions) => ({
                            type: CALLBACK_BUTTON,
                            label: trans('register', {}, 'actions'),
                            callback: () => selectedSessions.map(selectedSession => props.addGroups(selectedSession.id, selected, constants.LEARNER_TYPE))
                          })
                        }]
                      })
                    }]
                  }}
                />
              )
            }, {
              path: '/pending',
              render: () => (
                <SessionUsers
                  type={constants.LEARNER_TYPE}
                  course={props.course}
                  session={props.activeSession}
                  name={selectors.STORE_NAME+'.sessionPending'}
                  customDefinition={[
                    {
                      name: 'session',
                      label: trans('session', {}, 'cursus'),
                      type: 'training_session',
                      displayed: true,
                      displayable: true,
                      filterable: true,
                      options: {
                        course: props.course,
                        picker: {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}]
                        }
                      }
                    }, {
                      name: 'confirmed',
                      type: 'boolean',
                      label: trans('confirmed'),
                      displayable: true,
                      displayed: false
                    }, {
                      name: 'validated',
                      type: 'boolean',
                      label: trans('validated'),
                      displayable: true,
                      displayed: false
                    }
                  ]}
                  add={{
                    name: 'add_pending',
                    type: MODAL_BUTTON,
                    label: trans('add_pending', {}, 'cursus'),
                    modal: [MODAL_USERS, {
                      selectAction: (selected) => ({
                        type: MODAL_BUTTON,
                        label: trans('register', {}, 'actions'),
                        modal: [MODAL_SESSIONS, {
                          url: ['apiv2_cursus_course_list_sessions', {id: get(props.course, 'id')}],
                          filters: [{property: 'status', value: 'not_ended'}],
                          selectAction: (selectedSessions) => ({
                            type: CALLBACK_BUTTON,
                            label: trans('register', {}, 'actions'),
                            callback: () => selectedSessions.map(selectedSession => props.addPending(selectedSession.id, selected))
                          })
                        }]
                      })
                    }]
                  }}
                />
              )
            }, {
              path: '/cancellations',
              disabled: !hasPermission('register', props.activeSession),
              render() {
                const Cancellation = (
                  <Fragment>
                    <ListData
                      name={selectors.STORE_NAME+'.sessionCancellation'}
                      fetch={{
                        url: ['apiv2_cursus_session_list_cancellations', {id: props.activeSession.id}],
                        autoload: true
                      }}
                      delete={{
                        url: '',
                        displayed: () => false
                      }}
                      selectable={false}
                      definition={[
                        {
                          name: 'user',
                          type: 'user',
                          label: trans('user'),
                          displayed: true
                        }, {
                          name: 'date',
                          type: 'date',
                          label: trans('cancellation_date', {}, 'cursus'),
                          options: {time: true},
                          displayed: true,
                          filterable: false
                        }
                      ]}
                      card={(cardProps) => <UserCard {...cardProps} data={cardProps.data.user} />}
                      display={{
                        current: listConst.DISPLAY_TABLE
                      }}
                    />
                  </Fragment>
                )

                return Cancellation
              }
            }, {
              path: '/stats',
              onEnter: () => props.loadStats(props.course.id),
              disabled: !get(props.course, 'registration.form'),
              render: () => (
                <CourseStats
                  course={props.course}
                  stats={props.stats}
                />
              )
            }
          ]}
        />
      </div>
    </div>
  </Fragment>

CourseParticipants.propTypes = {
  path: T.string.isRequired,
  course: T.shape(
    CourseTypes.propTypes
  ).isRequired,
  activeSession: T.shape(
    SessionTypes.propTypes
  ),
  stats: T.object,
  addUsers: T.func.isRequired,
  addPending: T.func.isRequired,
  addGroups: T.func.isRequired,
  loadStats: T.func.isRequired,
  toggleVisibility: T.func.isRequired
}

export {
  CourseParticipants
}