<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dynamic Progress Display</title>
    <style>
     ::-webkit-scrollbar {
        width: 5px;
        height: 5px;
        display: none;
      }
      a {
          color: blue;
          text-decoration: none; 
          font-weight: bold; 
          text-decoration: underline;
          text-decoration-thickness: 2px;
          text-underline-offset: 3px; 
          cursor: pointer;
      }
      /* Track */
      ::-webkit-scrollbar-track {
        background: #888;
        border-radius: 5px;
      }

      /* Handle */
      ::-webkit-scrollbar-thumb {
        background: #4f4f4f;
        border-radius: 5px;
      }

      /* Handle on hover */
      ::-webkit-scrollbar-thumb:hover {
        background: #555;
      }
      /* Styling for the progress container */
      #progressContainer {
        display: flex;
        flex-direction: column;
        padding: 40px;
        gap: 10px;
        justify-content: center;
        /* overflow: auto; */
        margin: 40px;
      }

      /* Department styles */
      .department {
        display: flex;
        flex-direction: row;
        margin-bottom: 20px;
        max-width: 100%;
        width: 100%;
        gap: 8%;
        align-items: flex-start;
        position: relative;
        min-height: 120px;
      }

      .department .depName {
        height: 20px;
        max-height: 20px;
        text-wrap: wrap;
        max-width: 200px;
        min-width: 180px;
        text-align: center;
        padding-right: 20px;
        font-weight: bold;
        padding: 20px 14px;
        width: 200px;
        border: 1px solid;
        border-radius: 16px;
        height: fit-content;
        position: relative;
        font-size: 1rem;
        box-shadow: 0px 7px 16px rgba(171, 172, 180, 0.25);
      }

      .department .depName::after {
        position: absolute;
        content: "";
        width: calc(8vw - 7%);
        left: 100%;
        top: 50%;
        border: 1px solid;
        right: 0;
      }

      .department .navdotspan {
        height: 24px;
        width: 24px;
        border: 1px solid;
        border-radius: 100%;
        /* position: relative; */
        min-width: 24px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        top: 20px;
      }

      .department .navdotspan::after {
        content: "";
        width: calc(8vw - 7%);
        border: 1px solid;
        position: absolute;
        left: 25px;
        top: 50%;
      }

      .department:not(:last-child) {
        .navdotspan::before {
          content: "";
          height: var(--dot-height);
          border: 1px solid;
          position: absolute;
          left: 50%;
          top: 100%;
          border-color: #bbc2c7;
          z-index: -1;
        }
      }

      /* Keyframes for the pulsing animation */
      @keyframes pulse {
        0% {
          background-color: rgba(9, 128, 21, 1); /* Starting color */
          box-shadow: 0 0 5px rgba(9, 128, 21, 1);
        }
        50% {
          background-color: rgba(9, 128, 21, 0.24); /* Midpoint color */
          box-shadow: 0 0 20px rgba(9, 128, 21, 0.24);
        }
        100% {
          background-color: rgba(9, 128, 21, 0.24); /* Ending color */
          box-shadow: 0 0 5px rgba(9, 128, 21, 0.24);
        }
      }
      /* Keyframa for child current Index */
      @keyframes childpulse {
        0% {
          background-color: rgba(9, 128, 21, 1); /* Starting color */
          box-shadow: 0 0 5px rgba(9, 128, 21, 1);
        }
        50% {
          background-color: rgba(9, 128, 21, 0.24); /* Midpoint color */
          box-shadow: 0 0 20px rgba(9, 128, 21, 0.24);
        }
        100% {
          background-color: rgba(9, 128, 21, 0.24); /* Ending color */
          box-shadow: 0 0 5px rgba(9, 128, 21, 0.24);
        }
      }


      /* Apply animation to the current index */
      .current-index {
        animation: pulse 1.5s infinite; /* 1.5s duration, infinite repeats */
        transition: background-color 0.3s ease;
        border-radius: 8px; /* Optional rounded corners */
      }

       /* Apply animation to the current index */
       .current-childindex {
        animation: childpulse 1.5s infinite; /* 1.5s duration, infinite repeats */
        transition: background-color 0.3s ease;
        border-radius: 8px; /* Optional rounded corners */
      }

      .navdotspaninner {
        height: 12px;
        width: 12px;
        border: 1px solid;
        border-radius: 100%;
      }

      .childContainer {
        display: flex;
        gap: 30px;
        position: relative;
        top: 20px;
        width: 56vw;
        max-width: 56vw;
        min-width: 56vw;
        height: fit-content;
        max-height: fit-content;
        min-height: 90px;
      }

      .childOuter {
        display: flex;
        flex-direction: column;
        align-items: center;
        height: fit-content;
        max-width: 200px;
        text-align: center;
        gap: 15px;
        position: relative;
        top: 7px;
        min-width: fit-content;
      }

      /* .childOuter:first-child {
        .childDotNav::before {
          content: "";
          border: 1px solid;
          width: 100px;
          position: absolute;
          left: -102px;
          top: 5px;
          z-index: -1;
        }
      } */

      .childDotNav {
        height: 12px;
        min-height: 12px;
        width: 12px;
        border: 1px solid;
        border-radius: 100%;
        position: relative;
        z-index: 10;
        background-color: white;
      }

      /* .childDotNav::before {
        content: "";
        border: 1px solid;
        width: 168px;
        position: absolute;
        left: -170px;
        top: 5px;
        z-index: -1;
      } */

      .childOuter:last-child {
        .child::before {
          content: "";
          width: 50%;
          border: 1px solid;
          position: absolute;
          left: 0;
          top: -29px;
        }
      }
      .child {
        /* display: flex; */
        align-items: center;
        padding: 8px 15px;
        border: 1px solid;
        border-radius: 8px;
        min-width: 120px;
        position: relative;
        max-width: 200px;
        overflow-wrap: anywhere;
        top: 5px;
        display: flex;
        flex-direction: column;
        z-index: 0;
        gap: 5px;
      }

      .childarrow {
        position: absolute;
        height: 10px;
        width: 10px;
        transform: rotate(45deg);
        top: -6px;
        left: 46%;
        z-index: 10;
        border-left: 1px solid black;
        border-top: 1px solid;
      }

      .child::before {
        content: "";
        width: 120%;
        border: 1px solid;
        position: absolute;
        left: 0;
        top: -29px;
      }

      /* Progress bar styles */
      .child .progressBar {
        width: 100px;
        height: 10px;
        background-color: lightgray;
        border-radius: 5px;
        margin-right: 5px;
      }

      .child .progress {
        height: 100%;
        border-radius: 5px;
        background-color: blue; /* Progress color */
      }
    </style>
  </head>
  <body>
    <div id="progressContainer"></div>

    <script>
      // Simulating the API response with dynamic data
      //     function fetchData() {
      //       // Simulate a change in the JSON data on the server-side
      //       return new Promise((resolve) => {
      //           fetch('api.php')
      // .then(response => response.json())
      // .then(data => {
      //   resolve(data);
      // })
      // .catch(error => {
      //   console.error('There was an error!', error);
      // });
      //       });
      //     }

      window.addEventListener("load", () => {
  const savedScrollPosition = localStorage.getItem("childContainerScrollPosition");
  const childContainer = document.querySelector(".childContainer");

  if (savedScrollPosition && childContainer) {
    // Apply the saved scroll position
    childContainer.scrollLeft = parseInt(savedScrollPosition);
  }
});

// 2. Save the scroll position before the page unloads
window.addEventListener("beforeunload", () => {
  const childContainer = document.querySelector(".childContainer");
  if (childContainer) {
    // Store the current scroll position in localStorage
    localStorage.setItem("childContainerScrollPosition", childContainer.scrollLeft);
  }
});

      function fetchData() {
        // Simulate a change in the JSON data on the server-side
        return new Promise((resolve) => {
          // setTimeout(() => {
            // resolve([
            //   {
            //     dep_name: "Manuscript Structuring",
            //     id: 1,
            //     dep_color: "#298EBA",
            //     child: [
            //       {
            //         s_name: "Preparing File",
            //         code: "01",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Preparing File",
            //         code: "02",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Preparing File",
            //         code: "02",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Preparing File",
            //         code: "02",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Preparing File",
            //         code: "02",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Preparing File",
            //         code: "02",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Preparing File",
            //         code: "02",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Preparing File",
            //         code: "02",
            //         S_color: "rgba(208, 241, 255, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //     ],
            //   },
            //   {
            //     dep_name: "Draft",
            //     dep_color: "#CE7522",
            //     id: 2,
            //     child: [
            //       {
            //         s_name: "Finalizing the task",
            //         code: "03",
            //         S_color: "rgba(255, 231, 209, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Finalizing",
            //         code: "04",
            //         S_color: "rgba(255, 231, 209, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //       {
            //         s_name: "Finalizing",
            //         code: "04",
            //         S_color: "rgba(255, 231, 209, 0.4)",
            //         time: "00.10",
            //         progress: Math.random() * 100,
            //       },
            //     ],
            //   },
            // ]);


            fetch('http://103.66.12.3:8010/workflow-new/ce-editor-status/api.php?id=<?php echo $_REQUEST['aid'];?>')
  .then(response => response.json())
  .then(data => {
    resolve(data);
  })
  .catch(error => {
    console.error('There was an error!', error);
  });
          // }, 3000); // Simulate delay for API response (3 seconds)
        });
      }

      // Function to render progress data dynamically
      function renderProgress(data) {
        console.log(data);
        const container = document.getElementById("progressContainer");
        container.innerHTML = ""; // Clear previous content
        // Loop through the data and generate HTML
        data.map((department, i) => {
          // Create container for each department (dep_name)
          const departmentDiv = document.createElement("div");
          departmentDiv.classList.add("department");

          // Create and append dep_name element (aligned vertically)
          const depNameDiv = document.createElement("div");
          depNameDiv.classList.add("depName");
          const navdotspan = document.createElement("span");
          navdotspan.classList.add("navdotspan");

          const navdotspaninner = document.createElement("span");
          navdotspaninner.classList.add("navdotspaninner");
          depNameDiv.textContent = department.dep_name;
          departmentDiv.appendChild(depNameDiv);
          navdotspan.appendChild(navdotspaninner);
          departmentDiv.appendChild(navdotspan);
          if (department.dep_color) {
            
            depNameDiv.style.borderColor = department.dep_color;
            depNameDiv.style.color = department.dep_color;
            navdotspan.style.color = department.dep_color;
          }
          // Create the child progress bars (horizontally aligned)
          const childContainer = document.createElement("div");
          childContainer.classList.add("childContainer");
          childContainer.classList.add("childContainer"+i)
       
          department.child.forEach((child) => {
            const childDivOuter = document.createElement("div");
            childDivOuter.classList.add("childOuter");
            const childDiv = document.createElement("div");
            childDiv.classList.add("child");
            const childarrow = document.createElement("span");
            childarrow.classList.add("childarrow");
            childDiv.appendChild(childarrow);
            const childDotNav = document.createElement("span");
            childDotNav.classList.add("childDotNav");
            childDivOuter.appendChild(childDotNav);
            if (child.S_color && department.dep_color) {
              childDiv.style.backgroundColor = child.S_color;
              childDiv.style.borderColor = department.dep_color;
              childDiv.style.color = department.dep_color;
              childarrow.style.borderColor = department.dep_color;
              childarrow.style.backgroundColor = child.S_color;
              childDotNav.style.backgroundColor = department.dep_color;
              childDotNav.style.color = department.dep_color;
              childDiv.style.borderColor = department.dep_color;
            }
            // Append child name
            const childName = document.createElement("span");
            const childTime = document.createElement("span");
            childName.classList.add("childName");
            childName.innerHTML  = child.s_name;
            childName.style.color = "#222";
            childTime.textContent = child.time;
            childTime.style.color = "#222";
            childTime.style.fontWeight = "600";
            childDiv.appendChild(childName);
            childDiv.appendChild(childTime);
          
            if(childTime.textContent === "Running"){
              childDotNav.classList.add("current-childindex")
            }
            if (childTime.textContent == "Running") {
              navdotspan.classList.add("current-index");
              navdotspaninner.classList.add("current-index");
            } else {
              navdotspan.style.backgroundColor = department.dep_color;
            }
         
            childDivOuter.appendChild(childDiv);
            childContainer.appendChild(childDivOuter);
          });

          departmentDiv.appendChild(childContainer);
          container.appendChild(departmentDiv);
          childContainer.scrollLeft = childContainer.scrollWidth;
          let departmentheight = departmentDiv.offsetHeight;
          document.documentElement.style.setProperty(
            "--dot-height",
            `${departmentheight}px`
          );
        });
      }

      let savedscrollposition1 = 0;
      let savedscrollposition2 = 0;
      let savedscrollposition3 = 0;
      let verticalscrollpostition = 0;
 
      // Function to save scroll position
      function saveScrollPosition() {
        const childContainer1 = document.querySelector(".childContainer0");
        const childContainer2 = document.querySelector(".childContainer1");
        const childContainer3 = document.querySelector(".childContainer2");
        const parentContainer = document.querySelector("html");
        if(childContainer1){ 
          savedScrollPosition1 = childContainer1.scrollLeft;
        }if(childContainer2){
          savedScrollPosition2 = childContainer2.scrollLeft;
        }if(childContainer3){
          savedScrollPosition3 = childContainer3.scrollLeft;
        }
        verticalscrollpostition = parentContainer.scrollTop;
      }
 
      // Function to restore scroll position
      function restoreScrollPosition() {
        const childContainer1 = document.querySelector(".childContainer0");
        const childContainer2 = document.querySelector(".childContainer1");
        const childContainer3 = document.querySelector(".childContainer2");
        const parentContainer = document.querySelector("html");
        if(childContainer1){
          childContainer1.scrollLeft = savedScrollPosition1;
        }if(childContainer2){
          childContainer2.scrollLeft = savedScrollPosition2;
        }if(childContainer3){
          childContainer3.scrollLeft = savedScrollPosition3;
        }
        parentContainer.scrollTop = verticalscrollpostition;
      }

      //   Function to continuously update data from the "API"
       function updateDataPeriodically() {
         setInterval(async () => {
          saveScrollPosition();
           const newData = await fetchData(); // Fetch new data from the mock API
           renderProgress(newData);
           restoreScrollPosition(); // Render the updated data
         }, 10000); // Update every 10 seconds
       }

      // Initial data load
      (async function init() {
        const initialData = await fetchData();
        renderProgress(initialData);  
        updateDataPeriodically(); // Start periodic updates
      })();
      function ajax_reload(fsid) {
  fetch(`http://103.66.12.3:8010/workflow-new/ce-editor-status/update-error.php?fsid=` + fsid)
    .then(response => response) // Convert response to JSON
    .then(data => console.log('API Response:', data)) // Log response
    .catch(error => {
      console.error('There was an error!', error);
    });
}
    </script>
  </body>
</html>
