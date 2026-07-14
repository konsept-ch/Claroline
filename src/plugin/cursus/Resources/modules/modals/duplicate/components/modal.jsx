import React from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import omit from 'lodash/omit'

import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON} from '#/main/app/buttons'
import {Modal} from '#/main/app/overlays/modal/components/modal'

const defaultSessions = [
  {
    id: 'preview-session-1',
    name: 'Session 1',
    resources: ['Support PDF', 'Liste des participants'],
    defaultSession: true
  },
  {
    id: 'preview-session-2',
    name: 'Session 2',
    resources: ['Questionnaire', 'Lien de visio'],
    defaultSession: false
  },
  {
    id: 'preview-session-3',
    name: 'Session 3',
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

const DuplicateCourseModal = (props) => {
  const course = props.course || staticSourceCourse
  const sessions = get(course, 'sessions', defaultSessions)
  const tags = get(course, 'tags', [])
  const organizations = get(course, 'organizations', [])

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
              <input className="form-control" defaultValue={`${course.name || 'Formation'} - copie`} />
            </div>

            <div className="form-group">
              <label>Description</label>
              <textarea className="form-control" rows="3" defaultValue={course.plainDescription || course.description || ''} />
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
          <span className="text-muted">{sessions.length} sessions affichees</span>
        </div>

        <div className="table-responsive">
          <table className="table table-condensed">
            <thead>
              <tr>
                <th>Session</th>
                <th>Contenus lies</th>
                <th>Statut visuel</th>
              </tr>
            </thead>
            <tbody>
              {sessions.map((session, index) => (
                <tr key={session.id || index}>
                  <td>
                    <strong>{session.name}</strong>
                  </td>
                  <td>
                    {Array.isArray(session.resources) ? session.resources.join(', ') : 'Ressources de la session'}
                  </td>
                  <td>
                    {get(session, 'defaultSession', false) &&
                      <span className="label label-success">Session principale</span>
                    }
                    {!get(session, 'defaultSession', false) &&
                      <span className="label label-default">Session incluse</span>
                    }
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="alert alert-warning" style={{marginBottom: 0}}>
          Les inscriptions, participants, presences, dates operationnelles et historiques resteront exclus.
        </div>
      </div>

      <div className="modal-footer" style={{paddingLeft: 0, paddingRight: 0}}>
        <Button
          type={CALLBACK_BUTTON}
          className="btn btn-primary"
          label="Fermer"
          onClick={props.fadeModal}
        />
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
}

export {
  DuplicateCourseModal
}
