import React from 'react'
import {connect} from 'react-redux'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import omit from 'lodash/omit'

import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {Modal} from '#/main/app/overlays/modal/components/modal'
import {apiFetch} from '#/main/app/api/fetch'
import {MODAL_ORGANIZATIONS} from '#/main/core/modals/organizations'

const defaultSessions = [
  {
    id: 'preview-session-1',
    name: 'Session 1',
    startDate: '2026-09-01T09:00',
    endDate: '2026-09-01T17:00',
    resources: ['Support PDF', 'Liste des participants'],
    defaultSession: true
  },
  {
    id: 'preview-session-2',
    name: 'Session 2',
    startDate: '2026-09-08T09:00',
    endDate: '2026-09-08T17:00',
    resources: ['Questionnaire', 'Lien de visio'],
    defaultSession: false
  },
  {
    id: 'preview-session-3',
    name: 'Session 3',
    startDate: '2026-09-15T09:00',
    endDate: '2026-09-15T17:00',
    resources: ['Fiche d exercice'],
    defaultSession: false
  }
]

const staticSourceCourse = {
  id: 'preview-course',
  name: 'Formation de demonstration',
  description: 'Apercu visuel de la future fenetre de duplication.',
  plainDescription: 'Cette fenetre presente les informations de la formation source sans executer encore l action.',
  tags: ['Catalogue', 'Duplication'],
  organizations: ['Organisation pilote'],
  sessions: defaultSessions
}

const formatItems = (items = []) => items
  .map((item) => get(item, 'name', get(item, 'label', item)))
  .filter(Boolean)
  .join(', ')

const toDateTimeLocal = (value) => value ? String(value).slice(0, 16) : ''

const normalizeSession = (session) => {
  const dates = get(session, 'restrictions.dates', [])

  return {
    ...session,
    startDate: toDateTimeLocal(session.startDate || dates[0]),
    endDate: toDateTimeLocal(session.endDate || dates[1])
  }
}

const DuplicateCourseModal = (props) => {
  const [name, setName] = React.useState(null)
  const [description, setDescription] = React.useState(null)
  const [code, setCode] = React.useState(null)
  const [remainingSessions, setRemainingSessions] = React.useState(null)
  const [selectedOrganizations, setSelectedOrganizations] = React.useState(null)
  const [saving, setSaving] = React.useState(false)
  const [loadedSessions, setLoadedSessions] = React.useState(null)
  const course = props.course || staticSourceCourse
  const sessions = loadedSessions || get(course, 'sessions', course.id === staticSourceCourse.id ? defaultSessions : []).map(normalizeSession)
  const tags = get(course, 'tags', [])
  const organizations = get(course, 'organizations', [])
  const editableName = name === null ? `${course.name || 'Formation'} - copie` : name
  const editableCode = code === null ? (course.code || '') : code
  const editableDescription = description === null ? (course.plainDescription || course.description || '') : description
  const selectedSessions = remainingSessions || sessions
  const editableOrganizations = selectedOrganizations || organizations
  const updateSession = (id, field, value) => {
    setRemainingSessions(selectedSessions.map(session => session.id === id ? {...session, [field]: value} : session))
  }

  React.useEffect(() => {
    if (!course.id || course.id === staticSourceCourse.id || get(course, 'sessions.length', 0)) {
      return
    }

    const dispatch = typeof props.dispatch === 'function' ? props.dispatch : () => {}
    apiFetch({
      url: ['apiv2_cursus_course_list_sessions', {id: course.id}],
      request: {method: 'GET'},
      success: response => {
        const payload = get(response, 'data', response)
        const sessions = Array.isArray(payload) ? payload : get(payload, 'data', [])
        setLoadedSessions(sessions.map(normalizeSession))
      },
      error: () => setLoadedSessions([])
    }, dispatch)
  }, [course.id, props.dispatch])

  const duplicate = () => {
    setSaving(true)
    const dispatch = typeof props.dispatch === 'function' ? props.dispatch : () => {}
    apiFetch({
      url: ['apiv2_cursus_course_duplicate', {id: course.id}],
      request: {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          name: editableName,
          code: editableCode,
          plainDescription: editableDescription,
          organizations: editableOrganizations.map(organization => organization.id || organization.uuid || organization),
          sessions: selectedSessions.map(session => ({
            id: session.id,
            name: session.name,
            startDate: session.startDate,
            endDate: session.endDate
          }))
        })
      },
      success: () => {
        props.fadeModal()
        window.location.reload()
      },
      error: () => setSaving(false)
    }, dispatch)
  }
  return (
    <Modal
      {...omit(props, 'course')}
      icon="fa fa-fw fa-clone"
      className="duplicate-course-modal"
      bsSize="lg"
      title="Dupliquer une formation"
      subtitle={course.name || 'Apercu de duplication'}
    >
      <div className="row" style={{marginBottom: '1.5rem'}}>
        <div className="col-md-5">
          <div style={{
            background: 'linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%)',
            border: '1px solid #dbe5f3',
            borderRadius: '12px',
            padding: '1rem',
            height: '100%'
          }}>
            <div style={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.75rem'}}>
              <strong>Formation source</strong>
              <span className="label label-info">Apercu visuel</span>
            </div>

            <div style={{
              background: '#fff',
              border: '1px solid #dbe5f3',
              borderRadius: '10px',
              padding: '0.85rem'
            }}>
              <div style={{fontSize: '1.15rem', fontWeight: 600, marginBottom: '0.35rem'}}>
                {course.name || 'Formation sans nom'}
              </div>

              <div className="text-muted" style={{marginBottom: '0.75rem'}}>
                {course.description || course.plainDescription || 'Aucune description disponible.'}
              </div>

              {!!organizations.length &&
                <div style={{marginBottom: '0.75rem'}}>
                  <div style={{fontWeight: 600, marginBottom: '0.35rem'}}>Organisations</div>
                  <div>{formatItems(organizations)}</div>
                </div>
              }

              {!!tags.length &&
                <div>
                  <div style={{fontWeight: 600, marginBottom: '0.35rem'}}>Tags</div>
                  <div>
                    {tags.map((tag, index) =>
                      <span
                        key={`${tag}-${index}`}
                        className="label label-default"
                        style={{display: 'inline-block', marginRight: '0.35rem'}}
                      >
                        {tag}
                      </span>
                    )}
                  </div>
                </div>
              }
            </div>

            <div className="alert alert-info" style={{marginTop: '1rem', marginBottom: 0}}>
              Le createur de la copie sera l utilisateur connecte lors de la vraie implementation.
            </div>
          </div>
        </div>

        <div className="col-md-7">
          <div style={{
            background: '#fff',
            border: '1px solid #e4e8ef',
            borderRadius: '12px',
            padding: '1rem'
          }}>
            <h4 style={{marginTop: 0}}>Nouvelle formation</h4>

            <div className="form-group">
              <label>Nom</label>
              <input className="form-control" value={editableName} onChange={event => setName(event.target.value)} />
            </div>

            <div className="form-group">
              <label>Code</label>
              <input className="form-control" value={editableCode} onChange={event => setCode(event.target.value)} placeholder="Code de la formation" />
            </div>

            <div className="form-group">
              <label>Description</label>
              <textarea className="form-control" rows="3" value={editableDescription} onChange={event => setDescription(event.target.value)} />
            </div>

            <div className="form-group">
              <label>Organisations</label>
              {editableOrganizations.map((organization, index) => (
                <span key={`${get(organization, 'id', organization)}-${index}`} className="label label-default" style={{display: 'inline-block', marginRight: '0.35rem', marginBottom: '0.35rem'}}>
                  {get(organization, 'name', get(organization, 'label', organization))}
                  <Button
                    type={CALLBACK_BUTTON}
                    className="btn btn-link btn-xs"
                    label="×"
                    callback={() => setSelectedOrganizations(editableOrganizations.filter(item => item !== organization))}
                  />
                </span>
              ))}
              <Button
                type={MODAL_BUTTON}
                className="btn btn-default btn-block"
                icon="fa fa-fw fa-plus"
                label="Ajouter des organisations"
                modal={[MODAL_ORGANIZATIONS, {
                  title: 'Sélectionner les organisations',
                  initialSelection: editableOrganizations,
                  selectAction: (selected) => ({
                    type: CALLBACK_BUTTON,
                    label: 'Sélectionner',
                    callback: () => setSelectedOrganizations(selected)
                  })
                }]}
              />
            </div>
          </div>

        </div>
      </div>

      <div style={{
        background: '#fafbfc',
        border: '1px solid #e4e8ef',
        borderRadius: '12px',
        padding: '1rem'
      }}>
        <div style={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.75rem'}}>
          <h4 style={{margin: 0}}>Sessions incluses</h4>
          <span className="text-muted">{selectedSessions.length} sessions conservées</span>
        </div>

        <div className="table-responsive">
          <table className="table table-condensed">
            <thead>
              <tr>
                <th>Titre de la session</th>
                <th>Contenus lies</th>
                <th>Dates</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {selectedSessions.map((session, index) => (
                <tr key={session.id || index}>
                  <td>
                    <label className="sr-only">Titre de la session</label>
                    <input
                      type="text"
                      className="form-control"
                      value={session.name || ''}
                      onChange={event => updateSession(session.id, 'name', event.target.value)}
                    />
                  </td>
                  <td>
                    {Array.isArray(session.resources) ? session.resources.join(', ') : 'Ressources de la session'}
                  </td>
                  <td>
                    <label className="sr-only">Date de début</label>
                    <input type="datetime-local" value={session.startDate || ''} onChange={event => updateSession(session.id, 'startDate', event.target.value)} />
                    <label className="sr-only">Date de fin</label>
                    <input type="datetime-local" value={session.endDate || ''} onChange={event => updateSession(session.id, 'endDate', event.target.value)} />
                  </td>
                  <td>
                    <Button type={CALLBACK_BUTTON} className="btn btn-danger btn-xs" label="Supprimer" callback={() => setRemainingSessions(selectedSessions.filter(item => item.id !== session.id))} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="alert alert-warning" style={{marginBottom: 0}}>
          Les inscriptions, participants, présences, résultats et historiques resteront exclus. Les dates peuvent être modifiées avant la duplication.
        </div>
      </div>

      <div className="modal-footer" style={{paddingLeft: 0, paddingRight: 0}}>
        <Button
          type={CALLBACK_BUTTON}
          className="btn btn-default"
          label="Fermer"
          callback={props.fadeModal}
        />
        <Button type={CALLBACK_BUTTON} className="btn btn-primary" label={saving ? 'Duplication...' : 'OK / Dupliquer'} callback={duplicate} disabled={saving || !editableName.trim()} />
      </div>
    </Modal>
  )
}

DuplicateCourseModal.propTypes = {
  course: T.shape({
    id: T.string,
    name: T.string,
    description: T.string,
    plainDescription: T.string,
    tags: T.array,
    organizations: T.array,
    sessions: T.array
  }),
  fadeModal: T.func.isRequired
  ,dispatch: T.func
}

const ConnectedDuplicateCourseModal = connect(null, dispatch => ({dispatch}))(DuplicateCourseModal)

export {
  ConnectedDuplicateCourseModal as DuplicateCourseModal
}
