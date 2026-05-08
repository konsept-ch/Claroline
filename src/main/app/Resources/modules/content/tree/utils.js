import concat from 'lodash/concat'
import flatten from 'lodash/flatten'

function flattenTree(roots = []) {
  return flatten(roots.map(root => flattenChildren(root)))
}

function flattenChildren(object) {
  const children = object.children || []

  return concat([object], flatten(children.map(child => flattenChildren(child))))
}

export {
  flattenTree
}
