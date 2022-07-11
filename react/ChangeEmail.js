const ChangeEmail = () => {
  const [oldEmail, setOldEmail] = React.useState('')
  const [newEmail, setNewEmail] = React.useState('')
  const [loading, setLoading] = React.useState('')
  const [errorMessage, setErrorMessage] = React.useState()
  const [successMessage, setSuccessMessage] = React.useState()

  const onSubmit = async e => {
    e.preventDefault()

    setSuccessMessage(undefined)
    setErrorMessage(undefined)
    setLoading(true)
    setOldEmail('')
    setNewEmail('')

    const response = await fetch('rest/api.php?q=changeemail', {
      method: 'POST',
      body: JSON.stringify({ oldEmail, newEmail }),
    })

    // ignore responseString in this json. It's always "OK".
    const json = await response.json()
    setLoading(false)

    switch (response.status) {
      case 200:
        setSuccessMessage('Email address successfully changed.')
        break

      case 403:
        setErrorMessage(
          'You are not logged in as an administrator. Please reload the page and log in again.',
        )
        break

      case 404:
        setErrorMessage(`No account found with the email address ${json.oldEmail}.`)
        break

      case 409:
        setErrorMessage(`An account already exists with the email address ${json.newEmail}.`)
        break
    }
  }

  return (
    <form {...{ onSubmit }}>
      <TextField
        label='Old email address'
        value={oldEmail}
        changeHandler={e => setOldEmail(e.target.value)}
      />
      <TextField
        label='New email address'
        value={newEmail}
        changeHandler={e => setNewEmail(e.target.value)}
      />

      <div id='spacious-form-button-spinner'>
        {loading ? <img src='spinner.gif' /> : <button type='submit'>Save</button>}
      </div>

      {successMessage && <div className='confirmation'>{successMessage}</div>}
      {errorMessage && <div className='error'>{errorMessage}</div>}
    </form>
  )
}

ReactDOM.render(React.createElement(ChangeEmail), document.querySelector('#change-email'))
